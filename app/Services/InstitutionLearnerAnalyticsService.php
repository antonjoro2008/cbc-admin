<?php

namespace App\Services;

use App\Models\AssessmentAttempt;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InstitutionLearnerAnalyticsService
{
    /** @var array<int, int> */
    private array $assessmentTotalMarks = [];
    /**
     * Learner roster, CBE-style insights, and inclusion metrics for an institution cohort
     * (optionally scoped to a single classroom for teachers).
     *
     * @return array{classroom_id: int|null, students: Collection<int, User>, insights: array, inclusion_metrics: array}
     */
    public function learnerCohortAnalytics(int $institutionId, ?int $classroomId, int $attemptsLimit = 500): array
    {
        $students = User::query()
            ->where('institution_id', $institutionId)
            ->where('user_type', 'student')
            ->when($classroomId !== null, fn ($q) => $q->where('classroom_id', $classroomId))
            ->orderBy('name')
            ->get(['id', 'name', 'admission_number', 'grade_level', 'gender', 'guardian_email', 'guardian_phone', 'created_at']);

        $studentIds = $students->pluck('id')->all();

        if ($studentIds === []) {
            return [
                'classroom_id' => $classroomId,
                'students' => $students,
                'insights' => [
                    'average_percent' => 0.0,
                    'average_level' => '—',
                    'learners_improving_percent' => 0,
                ],
                'inclusion_metrics' => $this->emptyInclusionMetrics(0),
            ];
        }

        $attempts = AssessmentAttempt::query()
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('completed_at')
            ->with('assessment')
            ->orderBy('completed_at', 'desc')
            ->limit($attemptsLimit)
            ->get();

        $this->warmAssessmentMarksForAttempts($attempts);

        $avgPercent = 0.0;
        $percentCount = 0;
        foreach ($attempts as $a) {
            $outOf = $this->assessmentTotalMarks[(int) $a->assessment_id] ?? null;
            if ($outOf && $a->score !== null) {
                $avgPercent += (float) (($a->score / $outOf) * 100);
                $percentCount++;
            }
        }
        $avgPercent = $percentCount ? round($avgPercent / $percentCount, 2) : 0.0;

        return [
            'classroom_id' => $classroomId,
            'students' => $students,
            'insights' => [
                'average_percent' => $avgPercent,
                'average_level' => $this->competencyDescriptor($avgPercent),
                'learners_improving_percent' => $this->learnersImprovingPercent($attempts),
            ],
            'inclusion_metrics' => $this->buildInclusionMetrics($students, $attempts),
        ];
    }

    /**
     * Extra counts and chart series for the Filament institution admin dashboard.
     *
     * @return array{summary: array, chart_labels: list<string>, chart_values: list<int>}
     */
    public function institutionAdminExtras(int $institutionId): array
    {
        $studentIds = User::query()
            ->where('institution_id', $institutionId)
            ->where('user_type', 'student')
            ->pluck('id');

        $learners = $studentIds->count();

        $withGuardianEmail = User::query()
            ->where('institution_id', $institutionId)
            ->where('user_type', 'student')
            ->whereNotNull('guardian_email')
            ->where('guardian_email', '!=', '')
            ->count();

        $since = Carbon::now()->subDays(30)->startOfDay();
        $attempts30d = AssessmentAttempt::query()
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $since)
            ->count();

        $distinctLearners30d = AssessmentAttempt::query()
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $since)
            ->pluck('student_id')
            ->unique()
            ->count();

        $labels = [];
        $values = [];
        $indexByDay = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->startOfDay();
            $key = $day->format('Y-m-d');
            $indexByDay[$key] = count($labels);
            $labels[] = $day->format('M j');
            $values[] = 0;
        }

        $since = Carbon::now()->subDays(13)->startOfDay();
        $counts = AssessmentAttempt::query()
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $since)
            ->selectRaw('DATE(completed_at) as activity_day, COUNT(*) as total')
            ->groupBy('activity_day')
            ->pluck('total', 'activity_day');

        foreach ($counts as $day => $total) {
            $key = Carbon::parse($day)->format('Y-m-d');
            if (isset($indexByDay[$key])) {
                $values[$indexByDay[$key]] = (int) $total;
            }
        }

        return [
            'summary' => [
                'learners' => $learners,
                'teachers' => (int) User::query()->where('institution_id', $institutionId)->where('user_type', 'teacher')->count(),
                'classrooms' => (int) Classroom::query()->where('institution_id', $institutionId)->count(),
                'completed_attempts_last_30_days' => $attempts30d,
                'distinct_learners_active_last_30_days' => $distinctLearners30d,
                'learners_with_guardian_email' => $withGuardianEmail,
                'guardian_email_coverage_percent' => $learners > 0
                    ? round(($withGuardianEmail / $learners) * 100, 1)
                    : 0.0,
            ],
            'chart_labels' => $labels,
            'chart_values' => $values,
        ];
    }

    public static function genderLabel(string $key): string
    {
        return match ($key) {
            'female' => 'Female',
            'male' => 'Male',
            'non_binary' => 'Non-binary',
            'prefer_not_to_say' => 'Prefer not to say',
            'other' => 'Other',
            'unspecified' => 'Not recorded',
            default => ucfirst(str_replace('_', ' ', $key)),
        };
    }

    public static function genderColor(string $key): string
    {
        return match ($key) {
            'female' => '#EC4899',
            'male' => '#3B82F6',
            'non_binary' => '#8B5CF6',
            'prefer_not_to_say' => '#6B7280',
            'other' => '#F59E0B',
            'unspecified' => '#D1D5DB',
            default => '#94A3B8',
        };
    }

    /**
     * Roster counts, gender reporting coverage, and average outcomes by gender bucket (assessment attempts).
     */
    public function buildInclusionMetrics(Collection $students, Collection $attempts): array
    {
        $total = $students->count();
        $cohort = [];
        foreach (array_merge(User::GENDER_VALUES, ['unspecified']) as $g) {
            $cohort[$g] = 0;
        }
        $withGender = 0;
        foreach ($students as $s) {
            if ($s->gender && in_array($s->gender, User::GENDER_VALUES, true)) {
                $cohort[$s->gender]++;
                $withGender++;
            } else {
                $cohort['unspecified']++;
            }
        }

        $genderByStudentId = [];
        foreach ($students as $s) {
            $genderByStudentId[$s->id] = ($s->gender && in_array($s->gender, User::GENDER_VALUES, true))
                ? $s->gender
                : 'unspecified';
        }

        $agg = [];
        $this->warmAssessmentMarksForAttempts($attempts);
        foreach ($attempts as $a) {
            $outOf = $this->assessmentTotalMarks[(int) $a->assessment_id] ?? null;
            if (! $outOf || $a->score === null) {
                continue;
            }
            $pct = (float) (($a->score / $outOf) * 100);
            $bucket = $genderByStudentId[$a->student_id] ?? 'unspecified';
            if (! isset($agg[$bucket])) {
                $agg[$bucket] = ['sum' => 0.0, 'n' => 0, 'learners' => []];
            }
            $agg[$bucket]['sum'] += $pct;
            $agg[$bucket]['n']++;
            $agg[$bucket]['learners'][$a->student_id] = true;
        }

        $performance = [];
        foreach ($agg as $bucket => $row) {
            if ($row['n'] === 0) {
                continue;
            }
            $performance[$bucket] = [
                'average_percent' => round($row['sum'] / $row['n'], 2),
                'assessment_attempts' => $row['n'],
                'distinct_learners' => count($row['learners']),
            ];
        }

        $reportingRate = $total > 0 ? round(($withGender / $total) * 100, 1) : 0.0;

        return [
            'cohort_by_gender' => $cohort,
            'gender_reporting' => [
                'learners_with_gender' => $withGender,
                'learners_without_gender' => $total - $withGender,
                'reporting_rate_percent' => $reportingRate,
            ],
            'performance_by_gender' => $performance,
            'notes' => [
                'Cohort counts reflect current learners on your roster. Performance splits use completed assessment attempts only.',
                'Where gender is not recorded, learners appear under "Not recorded" for segmentation.',
                'Use small-N categories cautiously when interpreting averages for equity reviews.',
            ],
        ];
    }

    public function emptyInclusionMetrics(int $studentCount): array
    {
        $cohort = [];
        foreach (array_merge(User::GENDER_VALUES, ['unspecified']) as $g) {
            $cohort[$g] = 0;
        }

        return [
            'cohort_by_gender' => $cohort,
            'gender_reporting' => [
                'learners_with_gender' => 0,
                'learners_without_gender' => $studentCount,
                'reporting_rate_percent' => 0,
            ],
            'performance_by_gender' => [],
            'notes' => [
                'Cohort counts reflect current learners on your roster. Performance splits use completed assessment attempts only.',
                'Where gender is not recorded, learners appear under "Not recorded" for segmentation.',
            ],
        ];
    }

    /**
     * Kenya CBC/CBE wording: full descriptor plus acronym in brackets.
     */
    public function competencyDescriptor(float $p): string
    {
        if ($p < 50) {
            return 'Below Expectation (BE)';
        }
        if ($p <= 70) {
            return 'Approaching Expectation (AE)';
        }
        if ($p <= 85) {
            return 'Meeting Expectation (ME)';
        }

        return 'Exceeding Expectation (EE)';
    }

    public function learnersImprovingPercent(Collection $attempts): int
    {
        $byStudent = [];
        foreach ($attempts as $a) {
            $byStudent[$a->student_id][] = $a;
        }

        $with = 0;
        $improving = 0;
        foreach ($byStudent as $arr) {
            if (count($arr) < 2) {
                continue;
            }
            $sorted = collect($arr)->sortBy('completed_at')->values();
            $first = (float) ($sorted->first()->score ?? 0);
            $last = (float) ($sorted->last()->score ?? 0);
            $with++;
            if ($last > $first) {
                $improving++;
            }
        }

        return $with ? (int) round(($improving / $with) * 100) : 0;
    }

    /**
     * @param  Collection<int, AssessmentAttempt>  $attempts
     */
    private function warmAssessmentMarksForAttempts(Collection $attempts): void
    {
        $missingIds = $attempts
            ->pluck('assessment_id')
            ->filter()
            ->unique()
            ->filter(fn ($id) => ! isset($this->assessmentTotalMarks[(int) $id]));

        if ($missingIds->isEmpty()) {
            return;
        }

        $totals = DB::table('questions')
            ->whereIn('assessment_id', $missingIds)
            ->selectRaw('assessment_id, COALESCE(SUM(marks), 0) as total_marks')
            ->groupBy('assessment_id')
            ->pluck('total_marks', 'assessment_id');

        foreach ($missingIds as $assessmentId) {
            $this->assessmentTotalMarks[(int) $assessmentId] = (int) ($totals[$assessmentId] ?? 0);
        }
    }
}
