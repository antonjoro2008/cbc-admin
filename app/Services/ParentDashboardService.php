<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\ParentLearner;
use App\Models\User;
use Illuminate\Support\Collection;

class ParentDashboardService
{
    public function __construct(
        private readonly DashboardAnalyticsService $analytics,
        private readonly InstitutionLearnerAnalyticsService $cohortAnalytics,
    ) {}

    /**
     * Full parent dashboard analytics across all registered children.
     *
     * @return array<string, mixed>
     */
    public function build(User $parent): array
    {
        $parentLearners = $parent->parentLearners()->orderBy('name')->get();
        $learnerRows = [];
        $allPercents = [];
        $totalCompleted = 0;
        $linkedCount = 0;
        $improvingCount = 0;
        $withTrend = 0;

        foreach ($parentLearners as $learner) {
            $student = $this->resolveStudentAccount($parent, $learner);
            $row = $this->buildLearnerRow($learner, $student);
            $learnerRows[] = $row;

            if ($student) {
                $linkedCount++;
                $totalCompleted += (int) ($row['summary']['completed_attempts'] ?? 0);
                if (($row['summary']['average_percent'] ?? null) !== null) {
                    $allPercents[] = (float) $row['summary']['average_percent'];
                }
                if ($row['summary']['is_improving'] ?? false) {
                    $improvingCount++;
                }
                if ($row['summary']['has_progress_trend'] ?? false) {
                    $withTrend++;
                }
            }
        }

        $householdAverage = $allPercents !== []
            ? round(collect($allPercents)->avg(), 1)
            : 0.0;

        $improvingPercent = $withTrend > 0
            ? round(($improvingCount / $withTrend) * 100, 1)
            : 0.0;

        return [
            'overview' => [
                'registered_learners' => $parentLearners->count(),
                'linked_learners' => $linkedCount,
                'unlinked_learners' => max(0, $parentLearners->count() - $linkedCount),
                'total_completed_attempts' => $totalCompleted,
                'household_average_percent' => $householdAverage,
                'household_competency_level' => $this->cohortAnalytics->competencyDescriptor($householdAverage),
                'learners_improving_percent' => $improvingPercent,
                'note' => $linkedCount > 0
                    ? 'Progress shown for children linked to assessment accounts (by guardian contact or explicit link).'
                    : 'Add learners and ensure their assessment accounts use your email or phone as guardian contact to see progress.',
            ],
            'learners' => $learnerRows,
            'charts' => [
                'learners_by_grade' => $this->learnersByGradeChart($parentLearners),
                'children_average_scores' => [
                    'labels' => array_column($learnerRows, 'name'),
                    'values' => array_map(
                        fn (array $row): float => (float) ($row['summary']['average_percent'] ?? 0),
                        $learnerRows,
                    ),
                ],
            ],
            'action_items' => $this->actionItems($parentLearners->count(), $linkedCount, $totalCompleted),
            'insights' => [
                'average_percent' => $householdAverage,
                'average_level' => $this->cohortAnalytics->competencyDescriptor($householdAverage),
                'learners_improving_percent' => $improvingPercent,
            ],
        ];
    }

    /**
     * Household assessment stats for the main /api/dashboard payload.
     *
     * @return array<string, int|float>
     */
    public function householdAssessmentStats(User $parent): array
    {
        $payload = $this->build($parent);
        $overview = $payload['overview'];

        return [
            'total_attempts' => (int) ($overview['total_completed_attempts'] ?? 0),
            'completed_attempts' => (int) ($overview['total_completed_attempts'] ?? 0),
            'in_progress_attempts' => 0,
            'average_score' => (float) ($overview['household_average_percent'] ?? 0),
            'linked_learners' => (int) ($overview['linked_learners'] ?? 0),
        ];
    }

    public function parentCanViewStudent(User $parent, User $student): bool
    {
        if (! $parent->isParent() || ! $student->isStudent()) {
            return false;
        }

        if (ParentLearner::query()
            ->where('user_id', $parent->id)
            ->where('student_user_id', $student->id)
            ->exists()) {
            return true;
        }

        return $this->guardianMatchesParent($parent, $student)
            && $parent->parentLearners()
                ->get()
                ->contains(fn (ParentLearner $learner): bool => $this->namesLikelyMatch($learner->name, $student->name));
    }

    public function resolveStudentAccount(User $parent, ParentLearner $learner): ?User
    {
        if ($learner->student_user_id) {
            $student = User::query()
                ->where('id', $learner->student_user_id)
                ->where('user_type', 'student')
                ->first();

            if ($student && $this->parentCanViewStudent($parent, $student)) {
                return $student;
            }
        }

        $candidates = $this->guardianMatchedStudents($parent);
        if ($candidates->isEmpty()) {
            return null;
        }

        $name = strtolower(trim($learner->name));
        $match = $candidates->first(
            fn (User $s): bool => strtolower(trim($s->name)) === $name,
        ) ?? $candidates->first(
            fn (User $s): bool => $this->namesLikelyMatch($learner->name, $s->name),
        );

        return $match;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLearnerRow(ParentLearner $learner, ?User $student): array
    {
        $base = [
            'id' => $learner->id,
            'name' => $learner->name,
            'grade_level' => $learner->grade_level,
            'student_user_id' => $learner->student_user_id,
            'linked' => $student !== null,
            'created_at' => $learner->created_at?->toIso8601String(),
            'summary' => [
                'completed_attempts' => 0,
                'average_percent' => null,
                'competency_level' => 'No attempts yet',
                'last_activity_at' => null,
                'is_improving' => false,
                'has_progress_trend' => false,
            ],
            'recent_assessments' => [],
        ];

        if (! $student) {
            $base['link_hint'] = 'Use your email or phone on the child’s assessment account as guardian contact, or ask support to link accounts.';

            return $base;
        }

        $detail = $this->analytics->studentAnalytics($student);
        $overview = $detail['overview'] ?? [];
        $history = $detail['assessment_history'] ?? [];
        $trend = $overview['improvement_trend'] ?? 'stable';

        $base['student_user_id'] = $student->id;
        $base['linked'] = true;
        $base['summary'] = [
            'completed_attempts' => (int) ($overview['total_completed_attempts'] ?? 0),
            'average_percent' => isset($overview['average_percent'])
                ? round((float) $overview['average_percent'], 1)
                : null,
            'competency_level' => $overview['competency_level'] ?? 'No attempts yet',
            'last_activity_at' => $overview['last_activity_at'] ?? null,
            'is_improving' => $trend === 'improving',
            'has_progress_trend' => ($overview['total_completed_attempts'] ?? 0) >= 2,
            'improvement_trend' => $trend,
        ];
        $base['recent_assessments'] = array_slice($history, 0, 5);
        $base['strengths'] = array_slice($detail['strengths'] ?? [], 0, 2);
        $base['areas_for_improvement'] = array_slice($detail['areas_for_improvement'] ?? [], 0, 2);

        return $base;
    }

    /**
     * @return Collection<int, User>
     */
    private function guardianMatchedStudents(User $parent): Collection
    {
        $query = User::query()->where('user_type', 'student');

        if ($parent->email) {
            $query->where(function ($q) use ($parent): void {
                $q->where('guardian_email', $parent->email);
                if ($parent->phone_number) {
                    $q->orWhere('guardian_phone', $parent->phone_number);
                }
            });
        } elseif ($parent->phone_number) {
            $query->where('guardian_phone', $parent->phone_number);
        } else {
            return collect();
        }

        return $query->get();
    }

    private function guardianMatchesParent(User $parent, User $student): bool
    {
        if ($parent->email && strcasecmp((string) $student->guardian_email, (string) $parent->email) === 0) {
            return true;
        }

        if ($parent->phone_number && $student->guardian_phone === $parent->phone_number) {
            return true;
        }

        return false;
    }

    private function namesLikelyMatch(string $learnerName, string $studentName): bool
    {
        $a = strtolower(trim($learnerName));
        $b = strtolower(trim($studentName));

        if ($a === '' || $b === '') {
            return false;
        }

        return $a === $b || str_contains($b, $a) || str_contains($a, $b);
    }

    /**
     * @param  Collection<int, ParentLearner>  $learners
     * @return array{labels: list<string>, values: list<int>}
     */
    private function learnersByGradeChart(Collection $learners): array
    {
        $grouped = $learners->groupBy('grade_level');

        return [
            'labels' => $grouped->keys()->filter()->values()->all(),
            'values' => $grouped->map->count()->values()->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function actionItems(int $registered, int $linked, int $completedAttempts): array
    {
        if ($registered === 0) {
            return [[
                'priority' => 'medium',
                'type' => 'setup',
                'title' => 'Add your children',
                'description' => 'Register each learner you support, then link their assessment login for live progress.',
            ]];
        }

        $items = [];

        if ($linked < $registered) {
            $items[] = [
                'priority' => 'medium',
                'type' => 'link',
                'title' => 'Link assessment accounts',
                'description' => ($registered - $linked).' learner(s) are not linked yet. Use your guardian email or phone on their student profile.',
            ];
        }

        if ($completedAttempts === 0) {
            $items[] = [
                'priority' => 'high',
                'type' => 'engagement',
                'title' => 'Start practising',
                'description' => 'Encourage linked learners to complete assessments so you can track CBE progress here.',
            ];
        } elseif ($linked > 0) {
            $items[] = [
                'priority' => 'low',
                'type' => 'positive',
                'title' => 'Keep the momentum',
                'description' => 'Review each child’s progress card below and celebrate improvements.',
            ];
        }

        return $items;
    }
}
