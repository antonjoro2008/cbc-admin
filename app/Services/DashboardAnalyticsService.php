<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    private const CACHE_TTL_SECONDS = 300;

    /** @var array<int, int> */
    private array $assessmentTotalMarks = [];

    private ?array $adminAnalyticsMemory = null;

    /** @var array<int, array<string, mixed>> */
    private array $institutionAnalyticsMemory = [];

    /** @var array<string, array<string, mixed>> */
    private array $inclusionMetricsMemory = [];

    private ?array $platformInstitutionBreakdownMemory = null;

    private ?array $platformClassroomBreakdownMemory = null;

    public function __construct(
        private readonly InstitutionLearnerAnalyticsService $cohortAnalytics,
    ) {}

    /**
     * Clear cached dashboard analytics (e.g. after bulk data imports).
     */
    public static function flushCache(?int $institutionId = null): void
    {
        Cache::forget('dashboard.analytics.admin');

        if ($institutionId !== null) {
            Cache::forget("dashboard.analytics.institution.{$institutionId}");

            return;
        }

        foreach (Institution::pluck('id') as $id) {
            Cache::forget("dashboard.analytics.institution.{$id}");
        }
    }

    /**
     * Build role-specific analytics payload for the authenticated user.
     */
    public function buildForUser(User $user): array
    {
        return match (true) {
            $user->isStudent() => $this->studentAnalytics($user),
            $user->isInstitution() => $this->institutionAnalytics($user),
            $user->isTeacher() => $this->teacherAnalytics($user),
            $user->isParent() => $this->parentAnalytics($user),
            $user->isAdmin() => $this->adminAnalytics(),
            default => ['message' => 'Analytics not available for this user type'],
        };
    }

    /**
     * Comprehensive analytics for an individual student.
     */
    public function studentAnalytics(User $student): array
    {
        $attempts = AssessmentAttempt::query()
            ->where('student_id', $student->id)
            ->whereNotNull('completed_at')
            ->with(['assessment.subject'])
            ->orderBy('completed_at')
            ->get();

        $attemptPercents = $this->mapAttemptPercents($attempts);
        $averagePercent = $this->averageFromPercents($attemptPercents);

        $markedAnswers = AttemptAnswer::query()
            ->whereHas('attempt', fn ($q) => $q->where('student_id', $student->id)->whereNotNull('completed_at'))
            ->whereHas('feedback')
            ->with(['question', 'attempt.assessment.subject'])
            ->get();

        $categoryStats = $this->aggregateCategoryPerformance($markedAnswers);
        $subjectStats = $this->aggregateSubjectPerformance($attempts);

        $strengths = $this->topCategories($categoryStats, 5, descending: true);
        $weaknesses = $this->topCategories($categoryStats, 5, descending: false);
        $strengthsFormatted = $this->formatCategoryInsights($strengths, 'strength');
        $weaknessesFormatted = $this->formatCategoryInsights($weaknesses, 'improvement');

        $trend = $this->calculateImprovementTrend($attemptPercents);
        $competencyDistribution = $this->competencyDistribution($attemptPercents);

        return [
            'profile' => [
                'student_id' => $student->id,
                'name' => $student->name,
                'grade_level' => $student->grade_level,
                'institution_id' => $student->institution_id,
                'is_individual' => $student->institution_id === null,
            ],
            'overview' => [
                'average_percent' => $averagePercent,
                'competency_level' => $this->cohortAnalytics->competencyDescriptor($averagePercent),
                'total_completed_attempts' => $attempts->count(),
                'distinct_assessments' => $attempts->pluck('assessment_id')->unique()->count(),
                'improvement_trend' => $trend['direction'],
                'improvement_delta_percent' => $trend['delta'],
                'last_activity_at' => $attempts->last()?->completed_at?->toIso8601String(),
                'active_days_last_30' => $this->distinctActiveDays($attempts, 30),
            ],
            'competency_distribution' => $competencyDistribution,
            'charts' => [
                'performance_over_time' => $this->performanceOverTimeChart($attemptPercents),
                'activity_last_14_days' => $this->activityChartForStudent($student->id),
                'subject_performance' => $this->barChartFromStats($subjectStats),
                'category_performance' => $this->barChartFromStats($categoryStats),
            ],
            'strengths' => $strengthsFormatted,
            'areas_for_improvement' => $weaknessesFormatted,
            'subject_breakdown' => array_values($subjectStats),
            'category_breakdown' => array_values($categoryStats),
            'recent_performance' => $this->recentAttemptSummaries($attempts->sortByDesc('completed_at')->take(10)),
            'action_items' => $this->studentActionItems($weaknessesFormatted, $subjectStats, $trend, $attempts),
        ];
    }

    /**
     * Comprehensive analytics for an institution admin.
     */
    public function institutionAnalytics(User $institutionUser): array
    {
        if (! $institutionUser->institution_id) {
            return ['message' => 'Institution not linked to this account'];
        }

        return $this->institutionAnalyticsById((int) $institutionUser->institution_id);
    }

    /**
     * Full institution dashboard analytics by institution ID (admin drill-down).
     *
     * @return array<string, mixed>
     */
    public function institutionAnalyticsById(int $institutionId): array
    {
        if (isset($this->institutionAnalyticsMemory[$institutionId])) {
            return $this->institutionAnalyticsMemory[$institutionId];
        }

        return $this->institutionAnalyticsMemory[$institutionId] = Cache::remember(
            "dashboard.analytics.institution.{$institutionId}",
            self::CACHE_TTL_SECONDS,
            fn () => $this->computeInstitutionAnalyticsById($institutionId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function computeInstitutionAnalyticsById(int $institutionId): array
    {
        $cohort = $this->cohortAnalytics->learnerCohortAnalytics($institutionId, null, 1000);
        $extras = $this->cohortAnalytics->institutionAdminExtras($institutionId);

        $studentIds = User::query()
            ->where('institution_id', $institutionId)
            ->where('user_type', 'student')
            ->pluck('id');

        $attempts = AssessmentAttempt::query()
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('completed_at')
            ->with(['assessment.subject', 'student'])
            ->orderBy('completed_at', 'desc')
            ->limit(1000)
            ->get();

        $attemptPercents = $this->mapAttemptPercents($attempts);
        $learnerSummaries = $this->learnerPerformanceSummaries($studentIds, $attempts);
        $classroomBreakdown = $this->classroomBreakdown($institutionId, $attempts);
        $subjectPerformance = $this->aggregateSubjectPerformance($attempts);
        $inactiveLearners = $this->inactiveLearners($institutionId, 30);

        $markedAnswers = AttemptAnswer::query()
            ->whereHas('attempt', fn ($q) => $q->whereIn('student_id', $studentIds)->whereNotNull('completed_at'))
            ->whereHas('feedback')
            ->with(['question'])
            ->get();

        $categoryStats = $this->aggregateCategoryPerformance($markedAnswers);
        $categoryStrengths = $this->formatCategoryInsights($this->topCategories($categoryStats, 5, true), 'strength');
        $categoryWeaknesses = $this->formatCategoryInsights($this->topCategories($categoryStats, 5, false), 'improvement');

        return [
            'institution' => Institution::find($institutionId, ['id', 'name', 'motto', 'theme_color']),
            'summary' => $extras['summary'],
            'insights' => $cohort['insights'],
            'inclusion_metrics' => $cohort['inclusion_metrics'],
            'competency_distribution' => $this->competencyDistribution($attemptPercents),
            'charts' => [
                'activity_last_14_days' => [
                    'labels' => $extras['chart_labels'],
                    'values' => $extras['chart_values'],
                ],
                'classroom_comparison' => [
                    'labels' => array_column($classroomBreakdown, 'classroom_name'),
                    'values' => array_column($classroomBreakdown, 'average_percent'),
                ],
                'subject_performance' => $this->barChartFromStats($subjectPerformance),
                'category_performance' => $this->barChartFromStats($categoryStats),
                'grade_level_distribution' => $this->gradeLevelDistribution($institutionId),
                'gender_cohort' => $this->genderCohortChart($cohort['inclusion_metrics']['cohort_by_gender'] ?? []),
                'gender_performance' => $this->genderPerformanceChart($cohort['inclusion_metrics']['performance_by_gender'] ?? []),
            ],
            'classroom_breakdown' => $classroomBreakdown,
            'category_breakdown' => array_values($categoryStats),
            'class_strengths' => $categoryStrengths,
            'class_weaknesses' => $categoryWeaknesses,
            'top_performers' => array_slice($learnerSummaries['top'], 0, 10),
            'learners_needing_support' => array_slice($learnerSummaries['support'], 0, 10),
            'subject_breakdown' => array_values($subjectPerformance),
            'inactive_learners' => $inactiveLearners,
            'recent_activity' => $this->recentAttemptSummaries($attempts->take(15)),
            'action_items' => $this->institutionActionItems(
                $cohort['insights'],
                $inactiveLearners,
                $learnerSummaries['support'],
                $extras['summary'],
            ),
        ];
    }

    /**
     * Comprehensive analytics for a classroom teacher.
     */
    public function teacherAnalytics(User $teacher): array
    {
        if (! $teacher->institution_id || ! $teacher->classroom_id) {
            return [
                'classroom_id' => $teacher->classroom_id,
                'students' => [],
                'insights' => [
                    'average_percent' => 0,
                    'average_level' => '—',
                    'learners_improving_percent' => 0,
                ],
                'inclusion_metrics' => $this->cohortAnalytics->emptyInclusionMetrics(0),
                'message' => 'Assign a classroom to this teacher to view analytics.',
            ];
        }

        $cohort = $this->cohortAnalytics->learnerCohortAnalytics(
            $teacher->institution_id,
            $teacher->classroom_id,
            500,
        );

        $studentIds = $cohort['students']->pluck('id');
        $attempts = AssessmentAttempt::query()
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('completed_at')
            ->with(['assessment.subject', 'student'])
            ->orderBy('completed_at', 'desc')
            ->limit(500)
            ->get();

        $markedAnswers = AttemptAnswer::query()
            ->whereHas('attempt', fn ($q) => $q->whereIn('student_id', $studentIds)->whereNotNull('completed_at'))
            ->whereHas('feedback')
            ->with(['question', 'attempt.student'])
            ->get();

        $categoryStats = $this->aggregateCategoryPerformance($markedAnswers);
        $subjectStats = $this->aggregateSubjectPerformance($attempts);
        $learnerSummaries = $this->learnerPerformanceSummaries($studentIds, $attempts);
        $classroom = Classroom::with('teacher')->find($teacher->classroom_id);

        return [
            'classroom' => [
                'id' => $classroom?->id,
                'name' => $classroom?->name,
                'grade_level' => $classroom?->grade_level,
                'student_count' => $cohort['students']->count(),
            ],
            'students' => $cohort['students'],
            'insights' => $cohort['insights'],
            'inclusion_metrics' => $cohort['inclusion_metrics'],
            'competency_distribution' => $this->competencyDistribution($this->mapAttemptPercents($attempts)),
            'charts' => [
                'activity_last_14_days' => $this->activityChartForStudents($studentIds->all()),
                'subject_performance' => $this->barChartFromStats($subjectStats),
                'category_performance' => $this->barChartFromStats($categoryStats),
            ],
            'class_strengths' => $this->formatCategoryInsights($this->topCategories($categoryStats, 5, true), 'strength'),
            'class_weaknesses' => $this->formatCategoryInsights($this->topCategories($categoryStats, 5, false), 'improvement'),
            'subject_breakdown' => array_values($subjectStats),
            'top_performers' => array_slice($learnerSummaries['top'], 0, 5),
            'learners_needing_support' => array_slice($learnerSummaries['support'], 0, 5),
            'per_learner_summary' => $learnerSummaries['all'],
            'recent_activity' => $this->recentAttemptSummaries($attempts->take(10)),
            'action_items' => $this->teacherActionItems(
                $cohort['insights'],
                $learnerSummaries['support'],
                $this->topCategories($categoryStats, 3, false),
            ),
        ];
    }

    /**
     * Analytics for parent accounts (linked learner profiles).
     */
    public function parentAnalytics(User $parent): array
    {
        $learners = $parent->parentLearners()->get();

        return [
            'overview' => [
                'registered_learners' => $learners->count(),
                'note' => 'Link learner accounts to student users for full performance analytics.',
            ],
            'learners' => $learners->map(fn ($l) => [
                'id' => $l->id,
                'name' => $l->name,
                'grade_level' => $l->grade_level,
            ]),
            'charts' => [
                'learners_by_grade' => $this->parentGradeChart($learners),
            ],
            'action_items' => $learners->isEmpty()
                ? [[
                    'priority' => 'medium',
                    'type' => 'setup',
                    'title' => 'Add your children',
                    'description' => 'Register learner profiles so you can track their grade levels and progress.',
                ]]
                : [[
                    'priority' => 'low',
                    'type' => 'engagement',
                    'title' => 'Encourage practice',
                    'description' => 'Have learners complete assessments regularly to build competency data.',
                ]],
        ];
    }

    /**
     * Platform-wide analytics for admin users.
     */
    public function adminAnalytics(): array
    {
        if ($this->adminAnalyticsMemory !== null) {
            return $this->adminAnalyticsMemory;
        }

        return $this->adminAnalyticsMemory = Cache::remember(
            'dashboard.analytics.admin',
            self::CACHE_TTL_SECONDS,
            fn () => $this->computeAdminAnalytics(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function computeAdminAnalytics(): array
    {
        $since30 = Carbon::now()->subDays(30)->startOfDay();
        $since7 = Carbon::now()->subDays(7)->startOfDay();

        $studentCount = User::where('user_type', 'student')->count();
        $institutionStudents = User::where('user_type', 'student')->whereNotNull('institution_id')->count();
        $individualStudents = User::where('user_type', 'student')->whereNull('institution_id')->count();
        $institutionCount = Institution::count();
        $completedAttempts = AssessmentAttempt::whereNotNull('completed_at')->count();

        $attempts30d = AssessmentAttempt::whereNotNull('completed_at')
            ->where('completed_at', '>=', $since30)
            ->count();

        $revenue = Payment::where('status', 'successful')->sum('amount');

        $allStudentIds = User::where('user_type', 'student')->pluck('id');

        $attempts = AssessmentAttempt::query()
            ->whereIn('student_id', $allStudentIds)
            ->whereNotNull('completed_at')
            ->with(['assessment.subject', 'student'])
            ->orderBy('completed_at', 'desc')
            ->limit(800)
            ->get();

        $this->warmAssessmentMarksForAttempts($attempts);

        $attemptPercents = $this->mapAttemptPercents($attempts);
        $inclusion = $this->inclusionMetricsForScope();
        $institutionBreakdown = $this->platformInstitutionBreakdown();
        $subjectPerformance = $this->aggregateSubjectPerformance($attempts);
        $learnerSummaries = $this->learnerPerformanceSummaries(
            $attempts->pluck('student_id')->unique(),
            $attempts,
        );
        $classroomBreakdown = $this->platformClassroomBreakdown();
        $categoryStats = $this->platformCategoryBreakdown();
        $categoryStrengths = $this->formatCategoryInsights($this->topCategories($categoryStats, 8, true), 'strength');
        $categoryWeaknesses = $this->formatCategoryInsights($this->topCategories($categoryStats, 8, false), 'improvement');

        $withGuardianEmail = User::query()
            ->where('user_type', 'student')
            ->whereNotNull('guardian_email')
            ->where('guardian_email', '!=', '')
            ->count();
        $guardianCoverage = $studentCount > 0
            ? round(($withGuardianEmail / $studentCount) * 100, 1)
            : 0.0;
        $totalClassrooms = (int) Classroom::count();
        $learnersImprovingPercent = $this->cohortAnalytics->learnersImprovingPercent(
            $attempts->sortBy('completed_at')->values(),
        );
        $distinctLearners30d = AssessmentAttempt::query()
            ->whereIn('student_id', $allStudentIds)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $since30)
            ->distinct('student_id')
            ->count('student_id');

        $activityChart = $this->buildPlatformActivityLast14DaysChart();

        $genderCohort = $inclusion['cohort_by_gender'] ?? [];
        $genderPerformance = $inclusion['performance_by_gender'] ?? [];

        return [
            'overview' => [
                'total_users' => User::count(),
                'total_students' => $studentCount,
                'institution_students' => $institutionStudents,
                'individual_students' => $individualStudents,
                'total_institutions' => $institutionCount,
                'total_teachers' => User::where('user_type', 'teacher')->count(),
                'total_parents' => User::where('user_type', 'parent')->count(),
                'total_assessments' => Assessment::count(),
                'total_completed_attempts' => $completedAttempts,
                'attempts_last_30_days' => $attempts30d,
                'attempts_last_7_days' => AssessmentAttempt::whereNotNull('completed_at')
                    ->where('completed_at', '>=', $since7)->count(),
                'total_revenue_kes' => round((float) $revenue, 2),
                'successful_payments' => Payment::where('status', 'successful')->count(),
                'platform_average_percent' => $this->averageFromPercents($attemptPercents),
                'platform_competency_level' => $this->cohortAnalytics->competencyDescriptor(
                    $this->averageFromPercents($attemptPercents),
                ),
                'gender_reporting_rate_percent' => $inclusion['gender_reporting']['reporting_rate_percent'] ?? 0,
                'learners_improving_percent' => $learnersImprovingPercent,
                'guardian_email_coverage_percent' => $guardianCoverage,
                'learners_with_guardian_email' => $withGuardianEmail,
                'total_classrooms' => $totalClassrooms,
                'distinct_learners_active_last_30_days' => $distinctLearners30d,
            ],
            'insights' => [
                'average_percent' => $this->averageFromPercents($attemptPercents),
                'average_level' => $this->cohortAnalytics->competencyDescriptor($this->averageFromPercents($attemptPercents)),
                'learners_improving_percent' => $learnersImprovingPercent,
            ],
            'inclusion_metrics' => $inclusion,
            'competency_distribution' => $this->competencyDistribution($attemptPercents),
            'institution_breakdown' => $institutionBreakdown,
            'classroom_breakdown' => $classroomBreakdown,
            'subject_breakdown' => array_values($subjectPerformance),
            'category_breakdown' => array_values($categoryStats),
            'platform_strengths' => $categoryStrengths,
            'platform_weaknesses' => $categoryWeaknesses,
            'top_performers' => array_slice($learnerSummaries['top'], 0, 20),
            'learners_needing_support' => array_slice($learnerSummaries['support'], 0, 20),
            'grade_level_distribution' => $this->platformGradeLevelDistribution(),
            'charts' => [
                'activity_last_14_days' => $activityChart,
                'users_by_type' => [
                    'labels' => ['Students', 'Institutions', 'Teachers', 'Parents', 'Admins'],
                    'values' => [
                        $studentCount,
                        User::where('user_type', 'institution')->count(),
                        User::where('user_type', 'teacher')->count(),
                        User::where('user_type', 'parent')->count(),
                        User::where('user_type', 'admin')->count(),
                    ],
                ],
                'gender_cohort' => $this->genderCohortChart($genderCohort),
                'gender_performance' => $this->genderPerformanceChart($genderPerformance),
                'competency_distribution' => [
                    'labels' => ['Below (BE)', 'Approaching (AE)', 'Meeting (ME)', 'Exceeding (EE)'],
                    'values' => array_values($this->competencyDistribution($attemptPercents)),
                ],
                'subject_performance' => $this->barChartFromStats($subjectPerformance),
                'category_performance' => $this->barChartFromStats($categoryStats),
                'institution_comparison' => [
                    'labels' => array_column($institutionBreakdown, 'name'),
                    'values' => array_column($institutionBreakdown, 'average_percent'),
                ],
                'classroom_comparison' => [
                    'labels' => array_map(
                        fn ($r) => ($r['institution_name'] ?? '').' — '.$r['classroom_name'],
                        array_slice($classroomBreakdown, 0, 20),
                    ),
                    'values' => array_column(array_slice($classroomBreakdown, 0, 20), 'average_percent'),
                ],
                'student_type_split' => [
                    'labels' => ['Institution learners', 'Individual learners'],
                    'values' => [$institutionStudents, $individualStudents],
                ],
            ],
            'recent_activity' => $this->recentAttemptSummaries($attempts->take(20)),
            'action_items' => $this->adminActionItems($attempts30d, $studentCount, $inclusion),
        ];
    }

    /**
     * Gender & inclusion metrics for all students or a single institution.
     *
     * @return array<string, mixed>
     */
    public function inclusionMetricsForScope(?int $institutionId = null): array
    {
        $cacheKey = 'institution:'.($institutionId ?? 'platform');

        if (isset($this->inclusionMetricsMemory[$cacheKey])) {
            return $this->inclusionMetricsMemory[$cacheKey];
        }

        return $this->inclusionMetricsMemory[$cacheKey] = Cache::remember(
            "dashboard.analytics.inclusion.{$cacheKey}",
            self::CACHE_TTL_SECONDS,
            fn () => $this->computeInclusionMetricsForScope($institutionId),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function computeInclusionMetricsForScope(?int $institutionId): array
    {
        $students = User::query()
            ->where('user_type', 'student')
            ->when($institutionId !== null, fn ($q) => $q->where('institution_id', $institutionId))
            ->get(['id', 'name', 'gender', 'institution_id', 'grade_level', 'classroom_id']);

        if ($students->isEmpty()) {
            return $this->cohortAnalytics->emptyInclusionMetrics(0);
        }

        $attempts = AssessmentAttempt::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->whereNotNull('completed_at')
            ->with('assessment')
            ->orderBy('completed_at', 'desc')
            ->limit(500)
            ->get();

        $this->warmAssessmentMarksForAttempts($attempts);

        return $this->cohortAnalytics->buildInclusionMetrics($students, $attempts);
    }

    /**
     * Compare all schools/institutions plus individual learners on the platform.
     *
     * @return list<array<string, mixed>>
     */
    public function platformInstitutionBreakdown(int $limit = 30): array
    {
        if ($this->platformInstitutionBreakdownMemory !== null) {
            return array_slice($this->platformInstitutionBreakdownMemory, 0, $limit);
        }

        $this->platformInstitutionBreakdownMemory = $this->computePlatformInstitutionBreakdown();

        return array_slice($this->platformInstitutionBreakdownMemory, 0, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function computePlatformInstitutionBreakdown(): array
    {
        $breakdown = [];

        foreach (Institution::orderBy('name')->get(['id', 'name']) as $institution) {
            $studentIds = User::where('institution_id', $institution->id)
                ->where('user_type', 'student')
                ->pluck('id');

            $attempts = AssessmentAttempt::query()
                ->whereIn('student_id', $studentIds)
                ->whereNotNull('completed_at')
                ->with('assessment')
                ->limit(200)
                ->get();

            $this->warmAssessmentMarksForAttempts($attempts);
            $percents = $this->mapAttemptPercents($attempts);
            $avg = $this->averageFromPercents($percents);
            $withGender = User::whereIn('id', $studentIds)
                ->whereNotNull('gender')
                ->whereIn('gender', User::GENDER_VALUES)
                ->count();

            $breakdown[] = [
                'institution_id' => $institution->id,
                'name' => $institution->name,
                'student_count' => $studentIds->count(),
                'completed_attempts' => $attempts->count(),
                'average_percent' => $avg,
                'competency_level' => $this->cohortAnalytics->competencyDescriptor($avg),
                'gender_reporting_percent' => $studentIds->count() > 0
                    ? round(($withGender / $studentIds->count()) * 100, 1)
                    : 0,
            ];
        }

        $individualIds = User::where('user_type', 'student')->whereNull('institution_id')->pluck('id');
        if ($individualIds->isNotEmpty()) {
            $attempts = AssessmentAttempt::query()
                ->whereIn('student_id', $individualIds)
                ->whereNotNull('completed_at')
                ->with('assessment')
                ->limit(200)
                ->get();

            $this->warmAssessmentMarksForAttempts($attempts);
            $percents = $this->mapAttemptPercents($attempts);
            $avg = $this->averageFromPercents($percents);
            $withGender = User::whereIn('id', $individualIds)
                ->whereNotNull('gender')
                ->whereIn('gender', User::GENDER_VALUES)
                ->count();

            $breakdown[] = [
                'institution_id' => null,
                'name' => 'Individual learners (no school)',
                'student_count' => $individualIds->count(),
                'completed_attempts' => $attempts->count(),
                'average_percent' => $avg,
                'competency_level' => $this->cohortAnalytics->competencyDescriptor($avg),
                'gender_reporting_percent' => $individualIds->count() > 0
                    ? round(($withGender / $individualIds->count()) * 100, 1)
                    : 0,
            ];
        }

        usort($breakdown, fn ($a, $b) => $b['average_percent'] <=> $a['average_percent']);

        return $breakdown;
    }

    /**
     * All student performance summaries for platform admin tables.
     *
     * @return array{all: list<array>, top: list<array>, support: list<array>}
     */
    public function platformStudentSummaries(int $limit = 100): array
    {
        $data = $this->adminAnalytics();

        return [
            'all' => array_slice($data['top_performers'], 0, $limit),
            'top' => array_slice($data['top_performers'], 0, $limit),
            'support' => array_slice($data['learners_needing_support'], 0, $limit),
        ];
    }

    /**
     * Format inclusion metrics for Filament blade/chart widgets.
     *
     * @return array<string, mixed>
     */
    public function formatInclusionForView(array $inclusion, bool $hideEmptyCohortRows = true): array
    {
        $cohortRows = [];
        foreach ($inclusion['cohort_by_gender'] ?? [] as $key => $count) {
            $count = (int) $count;
            if ($hideEmptyCohortRows && $count === 0) {
                continue;
            }
            $cohortRows[] = [
                'label' => InstitutionLearnerAnalyticsService::genderLabel($key),
                'key' => $key,
                'count' => $count,
                'color' => InstitutionLearnerAnalyticsService::genderColor($key),
            ];
        }

        usort($cohortRows, fn ($a, $b) => $b['count'] <=> $a['count']);

        $performanceRows = [];
        foreach ($inclusion['performance_by_gender'] ?? [] as $key => $row) {
            $performanceRows[] = [
                'label' => InstitutionLearnerAnalyticsService::genderLabel($key),
                'key' => $key,
                'average_percent' => $row['average_percent'] ?? 0,
                'attempts' => $row['assessment_attempts'] ?? 0,
                'learners' => $row['distinct_learners'] ?? 0,
                'color' => InstitutionLearnerAnalyticsService::genderColor($key),
            ];
        }

        usort($performanceRows, fn ($a, $b) => $b['average_percent'] <=> $a['average_percent']);

        return [
            'notes' => $inclusion['notes'] ?? [],
            'gender_reporting' => $inclusion['gender_reporting'] ?? [],
            'cohort_rows' => $cohortRows,
            'performance_rows' => $performanceRows,
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    public function genderCohortChart(array $cohortByGender): array
    {
        $labels = [];
        $values = [];
        $colors = [];

        foreach ($cohortByGender as $key => $count) {
            if ((int) $count === 0) {
                continue;
            }
            $labels[] = InstitutionLearnerAnalyticsService::genderLabel($key);
            $values[] = (int) $count;
            $colors[] = InstitutionLearnerAnalyticsService::genderColor($key);
        }

        return compact('labels', 'values', 'colors');
    }

    /**
     * @return array{labels: list<string>, values: list<float>, colors: list<string>}
     */
    public function genderPerformanceChart(array $performanceByGender): array
    {
        $labels = [];
        $values = [];
        $colors = [];

        foreach ($performanceByGender as $key => $row) {
            $labels[] = InstitutionLearnerAnalyticsService::genderLabel($key);
            $values[] = (float) ($row['average_percent'] ?? 0);
            $colors[] = InstitutionLearnerAnalyticsService::genderColor($key);
        }

        return compact('labels', 'values', 'colors');
    }

    /**
     * All classrooms across every institution on the platform.
     *
     * @return list<array<string, mixed>>
     */
    public function platformClassroomBreakdown(int $limit = 50): array
    {
        if ($this->platformClassroomBreakdownMemory !== null) {
            return array_slice($this->platformClassroomBreakdownMemory, 0, $limit);
        }

        $this->platformClassroomBreakdownMemory = $this->computePlatformClassroomBreakdown();

        return array_slice($this->platformClassroomBreakdownMemory, 0, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function computePlatformClassroomBreakdown(): array
    {
        $breakdown = [];

        foreach (Classroom::with('institution:id,name')->orderBy('name')->get() as $classroom) {
            $studentIds = User::query()
                ->where('classroom_id', $classroom->id)
                ->where('user_type', 'student')
                ->pluck('id');

            $attempts = AssessmentAttempt::query()
                ->whereIn('student_id', $studentIds)
                ->whereNotNull('completed_at')
                ->with('assessment')
                ->limit(100)
                ->get();

            $this->warmAssessmentMarksForAttempts($attempts);
            $percents = $this->mapAttemptPercents($attempts);
            $avg = $this->averageFromPercents($percents);

            $breakdown[] = [
                'classroom_id' => $classroom->id,
                'classroom_name' => $classroom->name,
                'institution_id' => $classroom->institution_id,
                'institution_name' => $classroom->institution?->name ?? '—',
                'grade_level' => $classroom->grade_level,
                'student_count' => $studentIds->count(),
                'completed_attempts' => $attempts->count(),
                'average_percent' => $avg,
                'competency_level' => $this->cohortAnalytics->competencyDescriptor($avg),
            ];
        }

        usort($breakdown, fn ($a, $b) => $b['average_percent'] <=> $a['average_percent']);

        return $breakdown;
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function platformGradeLevelDistribution(): array
    {
        $rows = User::query()
            ->where('user_type', 'student')
            ->whereNotNull('grade_level')
            ->selectRaw('grade_level, COUNT(*) as count')
            ->groupBy('grade_level')
            ->orderBy('grade_level')
            ->get();

        return [
            'labels' => $rows->pluck('grade_level')->all(),
            'values' => $rows->pluck('count')->map(fn ($c) => (int) $c)->all(),
        ];
    }

    /**
     * Analytics for a specific student (institution/teacher drill-down).
     */
    public function studentAnalyticsById(int $studentId): array
    {
        $student = User::where('id', $studentId)->where('user_type', 'student')->firstOrFail();

        return $this->studentAnalytics($student);
    }

    // -------------------------------------------------------------------------
    // Shared computation helpers
    // -------------------------------------------------------------------------

    public function platformCategoryBreakdown(): array
    {
        $markedAnswers = AttemptAnswer::query()
            ->whereHas('attempt', fn ($q) => $q->whereNotNull('completed_at'))
            ->whereHas('feedback')
            ->with(['question:id,category_tag,marks'])
            ->limit(1500)
            ->get(['id', 'attempt_id', 'question_id', 'marks_awarded']);

        return $this->aggregateCategoryPerformance($markedAnswers);
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildPlatformActivityLast14DaysChart(): array
    {
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

        return ['labels' => $labels, 'values' => $values];
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

    /**
     * @param  Collection<int, AssessmentAttempt>  $attempts
     * @return list<array{attempt_id: int, completed_at: string|null, percent: float, label: string}>
     */
    private function mapAttemptPercents(Collection $attempts): array
    {
        $this->warmAssessmentMarksForAttempts($attempts);

        $result = [];
        foreach ($attempts as $attempt) {
            $percent = $this->attemptPercent($attempt);
            if ($percent === null) {
                continue;
            }
            $result[] = [
                'attempt_id' => $attempt->id,
                'completed_at' => $attempt->completed_at?->toIso8601String(),
                'percent' => $percent,
                'label' => $attempt->completed_at?->format('M j') ?? 'Attempt '.$attempt->id,
                'assessment_title' => $attempt->assessment?->title,
                'subject_name' => $attempt->assessment?->subject?->name,
            ];
        }

        return $result;
    }

    private function attemptPercent(AssessmentAttempt $attempt): ?float
    {
        $assessmentId = (int) $attempt->assessment_id;
        if ($assessmentId && ! isset($this->assessmentTotalMarks[$assessmentId])) {
            $this->warmAssessmentMarksForAttempts(collect([$attempt]));
        }

        $outOf = $this->assessmentTotalMarks[$assessmentId] ?? null;
        if (! $outOf || $attempt->score === null) {
            return null;
        }

        return round(((float) $attempt->score / (float) $outOf) * 100, 2);
    }

    /**
     * @param  list<array{percent: float}>  $percents
     */
    private function averageFromPercents(array $percents): float
    {
        if ($percents === []) {
            return 0.0;
        }

        return round(collect($percents)->avg('percent'), 2);
    }

    /**
     * @param  Collection<int, AttemptAnswer>  $answers
     * @return array<string, array{label: string, average_percent: float, competency_level: string, questions_answered: int, marks_awarded: int, marks_possible: int}>
     */
    private function aggregateCategoryPerformance(Collection $answers): array
    {
        $grouped = [];
        foreach ($answers as $answer) {
            $tag = $answer->question?->category_tag ?: 'Uncategorized';
            if (! isset($grouped[$tag])) {
                $grouped[$tag] = ['marks_awarded' => 0, 'marks_possible' => 0, 'count' => 0];
            }
            $grouped[$tag]['marks_awarded'] += (int) ($answer->marks_awarded ?? 0);
            $grouped[$tag]['marks_possible'] += (int) ($answer->question?->marks ?? 0);
            $grouped[$tag]['count']++;
        }

        $stats = [];
        foreach ($grouped as $tag => $row) {
            if ($row['marks_possible'] <= 0) {
                continue;
            }
            $pct = round(($row['marks_awarded'] / $row['marks_possible']) * 100, 2);
            $stats[$tag] = [
                'label' => $tag,
                'average_percent' => $pct,
                'competency_level' => $this->cohortAnalytics->competencyDescriptor($pct),
                'questions_answered' => $row['count'],
                'marks_awarded' => $row['marks_awarded'],
                'marks_possible' => $row['marks_possible'],
            ];
        }

        uasort($stats, fn ($a, $b) => $b['average_percent'] <=> $a['average_percent']);

        return $stats;
    }

    /**
     * @param  Collection<int, AssessmentAttempt>  $attempts
     * @return array<string, array{label: string, average_percent: float, competency_level: string, attempts_count: int}>
     */
    private function aggregateSubjectPerformance(Collection $attempts): array
    {
        $grouped = [];
        foreach ($attempts as $attempt) {
            $subject = $attempt->assessment?->subject?->name ?? 'General';
            $percent = $this->attemptPercent($attempt);
            if ($percent === null) {
                continue;
            }
            $grouped[$subject]['percents'][] = $percent;
            $grouped[$subject]['attempts'] = ($grouped[$subject]['attempts'] ?? 0) + 1;
        }

        $stats = [];
        foreach ($grouped as $subject => $row) {
            $avg = round(collect($row['percents'])->avg(), 2);
            $stats[$subject] = [
                'label' => $subject,
                'average_percent' => $avg,
                'competency_level' => $this->cohortAnalytics->competencyDescriptor($avg),
                'attempts_count' => $row['attempts'],
            ];
        }

        uasort($stats, fn ($a, $b) => $b['average_percent'] <=> $a['average_percent']);

        return $stats;
    }

    /**
     * @param  array<string, array{average_percent: float}>  $stats
     * @return list<array<string, mixed>>
     */
    private function topCategories(array $stats, int $limit, bool $descending): array
    {
        $sorted = $stats;
        uasort($sorted, fn ($a, $b) => $descending
            ? $b['average_percent'] <=> $a['average_percent']
            : $a['average_percent'] <=> $b['average_percent']);

        return array_slice(array_values($sorted), 0, $limit);
    }

    /**
     * @param  list<array{percent: float}>  $attemptPercents
     * @return array{direction: string, delta: float}
     */
    private function calculateImprovementTrend(array $attemptPercents): array
    {
        if (count($attemptPercents) < 2) {
            return ['direction' => 'insufficient_data', 'delta' => 0.0];
        }

        $mid = (int) floor(count($attemptPercents) / 2);
        $firstHalf = array_slice($attemptPercents, 0, $mid);
        $secondHalf = array_slice($attemptPercents, $mid);

        $firstAvg = $this->averageFromPercents($firstHalf);
        $secondAvg = $this->averageFromPercents($secondHalf);
        $delta = round($secondAvg - $firstAvg, 2);

        $direction = match (true) {
            abs($delta) < 3 => 'stable',
            $delta > 0 => 'improving',
            default => 'declining',
        };

        return ['direction' => $direction, 'delta' => $delta];
    }

    /**
     * @param  list<array{percent: float}>  $attemptPercents
     * @return array{BE: int, AE: int, ME: int, EE: int}
     */
    private function competencyDistribution(array $attemptPercents): array
    {
        $dist = ['BE' => 0, 'AE' => 0, 'ME' => 0, 'EE' => 0];
        foreach ($attemptPercents as $row) {
            $dist[$this->competencyKey($row['percent'])]++;
        }

        return $dist;
    }

    private function competencyKey(float $percent): string
    {
        if ($percent < 50) {
            return 'BE';
        }
        if ($percent <= 70) {
            return 'AE';
        }
        if ($percent <= 85) {
            return 'ME';
        }

        return 'EE';
    }

    /**
     * @param  list<array{label: string, average_percent: float}>  $stats
     * @return array{labels: list<string>, values: list<float>}
     */
    private function barChartFromStats(array $stats): array
    {
        return [
            'labels' => array_column($stats, 'label'),
            'values' => array_map(fn ($s) => $s['average_percent'] ?? $s['percent'] ?? 0, array_values($stats)),
        ];
    }

    /**
     * @param  list<array{label: string, completed_at: string|null, percent: float}>  $attemptPercents
     * @return array{labels: list<string>, values: list<float>}
     */
    private function performanceOverTimeChart(array $attemptPercents): array
    {
        $recent = array_slice($attemptPercents, -20);

        return [
            'labels' => array_column($recent, 'label'),
            'values' => array_column($recent, 'percent'),
        ];
    }

    private function activityChartForStudent(int $studentId): array
    {
        return $this->activityChartForStudents([$studentId]);
    }

    /**
     * @param  list<int>  $studentIds
     * @return array{labels: list<string>, values: list<int>}
     */
    private function activityChartForStudents(array $studentIds): array
    {
        $labels = [];
        $values = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = Carbon::now()->subDays($i)->startOfDay();
            $labels[] = $day->format('M j');
            $values[] = $studentIds === []
                ? 0
                : (int) AssessmentAttempt::query()
                    ->whereIn('student_id', $studentIds)
                    ->whereNotNull('completed_at')
                    ->whereBetween('completed_at', [$day, (clone $day)->endOfDay()])
                    ->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @param  Collection<int, AssessmentAttempt>  $attempts
     */
    private function distinctActiveDays(Collection $attempts, int $days): int
    {
        $since = Carbon::now()->subDays($days)->startOfDay();

        return $attempts
            ->filter(fn ($a) => $a->completed_at && $a->completed_at >= $since)
            ->map(fn ($a) => $a->completed_at->format('Y-m-d'))
            ->unique()
            ->count();
    }

    /**
     * @param  list<array<string, mixed>>  $categories
     * @return list<array<string, mixed>>
     */
    private function formatCategoryInsights(array $categories, string $type): array
    {
        return array_map(function ($cat) use ($type) {
            $insight = $type === 'strength'
                ? "Strong performance in {$cat['label']} — keep building on this area."
                : "Focus practice on {$cat['label']} to raise competency.";

            return array_merge($cat, [
                'insight' => $insight,
                'recommended_action' => $type === 'improvement'
                    ? "Complete more {$cat['label']} assessments and review marked feedback."
                    : "Maintain excellence in {$cat['label']} with challenging assessments.",
            ]);
        }, $categories);
    }

    /**
     * @param  Collection<int, AssessmentAttempt>  $attempts
     * @return list<array<string, mixed>>
     */
    private function recentAttemptSummaries(Collection $attempts): array
    {
        return $attempts->map(function (AssessmentAttempt $attempt) {
            $percent = $this->attemptPercent($attempt);

            return [
                'attempt_id' => $attempt->id,
                'assessment_title' => $attempt->assessment?->title,
                'subject' => $attempt->assessment?->subject?->name,
                'student_name' => $attempt->student?->name,
                'score' => $attempt->score,
                'percent' => $percent,
                'competency_level' => $percent !== null
                    ? $this->cohortAnalytics->competencyDescriptor($percent)
                    : null,
                'completed_at' => $attempt->completed_at?->toIso8601String(),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, int>|\Illuminate\Support\Collection<int, int>  $studentIds
     * @param  Collection<int, AssessmentAttempt>  $attempts
     * @return array{all: list<array>, top: list<array>, support: list<array>}
     */
    private function learnerPerformanceSummaries($studentIds, Collection $attempts): array
    {
        $byStudent = [];
        foreach ($attempts as $attempt) {
            $percent = $this->attemptPercent($attempt);
            if ($percent === null) {
                continue;
            }
            $byStudent[$attempt->student_id]['percents'][] = $percent;
            $byStudent[$attempt->student_id]['attempts'] = ($byStudent[$attempt->student_id]['attempts'] ?? 0) + 1;
        }

        $summaries = [];
        $studentIdsWithAttempts = array_keys($byStudent);
        if ($studentIdsWithAttempts === []) {
            return ['all' => [], 'top' => [], 'support' => []];
        }

        $studentsById = User::query()
            ->with('institution:id,name')
            ->whereIn('id', $studentIdsWithAttempts)
            ->get(['id', 'name', 'admission_number', 'grade_level', 'classroom_id', 'gender', 'institution_id'])
            ->keyBy('id');

        foreach ($studentsById as $id => $student) {
            $row = $byStudent[$id] ?? null;
            $avg = $row ? round(collect($row['percents'])->avg(), 2) : 0.0;
            $genderKey = ($student->gender && in_array($student->gender, User::GENDER_VALUES, true))
                ? $student->gender
                : 'unspecified';
            $summaries[] = [
                'student_id' => $id,
                'name' => $student->name,
                'admission_number' => $student->admission_number,
                'grade_level' => $student->grade_level,
                'gender' => InstitutionLearnerAnalyticsService::genderLabel($genderKey),
                'gender_key' => $genderKey,
                'institution_name' => $student->institution?->name ?? 'Individual',
                'average_percent' => $avg,
                'competency_level' => $this->cohortAnalytics->competencyDescriptor($avg),
                'completed_attempts' => $row['attempts'] ?? 0,
            ];
        }

        usort($summaries, fn ($a, $b) => $b['average_percent'] <=> $a['average_percent']);

        $withAttempts = array_values(array_filter($summaries, fn ($s) => $s['completed_attempts'] > 0));
        $support = array_values(array_filter($withAttempts, fn ($s) => $s['average_percent'] < 50));

        return [
            'all' => $summaries,
            'top' => $withAttempts,
            'support' => $support,
        ];
    }

    /**
     * @param  Collection<int, AssessmentAttempt>  $attempts
     * @return list<array<string, mixed>>
     */
    private function classroomBreakdown(int $institutionId, Collection $attempts): array
    {
        $classrooms = Classroom::where('institution_id', $institutionId)->get();
        $breakdown = [];

        foreach ($classrooms as $classroom) {
            $classStudentIds = User::where('classroom_id', $classroom->id)
                ->where('user_type', 'student')
                ->pluck('id');

            $classAttempts = $attempts->whereIn('student_id', $classStudentIds);
            $percents = $this->mapAttemptPercents($classAttempts);
            $avg = $this->averageFromPercents($percents);

            $breakdown[] = [
                'classroom_id' => $classroom->id,
                'classroom_name' => $classroom->name,
                'grade_level' => $classroom->grade_level,
                'student_count' => $classStudentIds->count(),
                'average_percent' => $avg,
                'competency_level' => $this->cohortAnalytics->competencyDescriptor($avg),
                'completed_attempts' => $classAttempts->count(),
            ];
        }

        usort($breakdown, fn ($a, $b) => $b['average_percent'] <=> $a['average_percent']);

        return $breakdown;
    }

    private function gradeLevelDistribution(int $institutionId): array
    {
        $rows = User::query()
            ->where('institution_id', $institutionId)
            ->where('user_type', 'student')
            ->whereNotNull('grade_level')
            ->selectRaw('grade_level, COUNT(*) as count')
            ->groupBy('grade_level')
            ->orderBy('grade_level')
            ->get();

        return [
            'labels' => $rows->pluck('grade_level')->all(),
            'values' => $rows->pluck('count')->map(fn ($c) => (int) $c)->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function inactiveLearners(int $institutionId, int $days): array
    {
        $since = Carbon::now()->subDays($days)->startOfDay();

        return User::query()
            ->where('institution_id', $institutionId)
            ->where('user_type', 'student')
            ->whereDoesntHave('assessmentAttempts', fn ($q) => $q
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', $since))
            ->limit(20)
            ->get(['id', 'name', 'admission_number', 'grade_level', 'classroom_id'])
            ->map(fn ($s) => [
                'student_id' => $s->id,
                'name' => $s->name,
                'admission_number' => $s->admission_number,
                'grade_level' => $s->grade_level,
                'days_inactive' => $days,
            ])
            ->all();
    }

    /**
     * @param  Collection<int, \App\Models\ParentLearner>  $learners
     */
    private function parentGradeChart(Collection $learners): array
    {
        $grouped = $learners->groupBy('grade_level');

        return [
            'labels' => $grouped->keys()->filter()->values()->all(),
            'values' => $grouped->map->count()->values()->all(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $weaknesses
     * @param  array<string, array>  $subjectStats
     * @param  array{direction: string}  $trend
     * @param  Collection<int, AssessmentAttempt>  $attempts
     * @return list<array<string, mixed>>
     */
    private function studentActionItems(array $weaknesses, array $subjectStats, array $trend, Collection $attempts): array
    {
        $items = [];

        if ($attempts->isEmpty()) {
            $items[] = [
                'priority' => 'high',
                'type' => 'start',
                'title' => 'Take your first assessment',
                'description' => 'Complete an assessment to unlock personalised strength and weakness insights.',
            ];

            return $items;
        }

        foreach (array_slice($weaknesses, 0, 3) as $weak) {
            $action = $weak['recommended_action'] ?? "Complete more {$weak['label']} assessments and review marked feedback.";
            $items[] = [
                'priority' => 'high',
                'type' => 'practice',
                'title' => "Improve {$weak['label']}",
                'description' => "Current level: {$weak['competency_level']}. {$action}",
            ];
        }

        if ($trend['direction'] === 'declining') {
            $items[] = [
                'priority' => 'medium',
                'type' => 'trend',
                'title' => 'Performance is declining',
                'description' => 'Review recent feedback and revisit topics where scores dropped.',
            ];
        }

        $lowestSubject = collect($subjectStats)->sortBy('average_percent')->first();
        if ($lowestSubject && $lowestSubject['average_percent'] < 60) {
            $items[] = [
                'priority' => 'medium',
                'type' => 'subject',
                'title' => "Focus on {$lowestSubject['label']}",
                'description' => "Subject average is {$lowestSubject['average_percent']}%. Target assessments in this subject next.",
            ];
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $inactiveLearners
     * @param  list<array<string, mixed>>  $supportLearners
     */
    private function institutionActionItems(array $insights, array $inactiveLearners, array $supportLearners, array $summary): array
    {
        $items = [];

        if (count($supportLearners) > 0) {
            $items[] = [
                'priority' => 'high',
                'type' => 'intervention',
                'title' => count($supportLearners).' learners below expectation',
                'description' => 'Review learners scoring below 50% and assign targeted practice assessments.',
            ];
        }

        if (count($inactiveLearners) > 0) {
            $items[] = [
                'priority' => 'medium',
                'type' => 'engagement',
                'title' => count($inactiveLearners).' inactive learners (30 days)',
                'description' => 'Follow up with teachers and guardians to re-engage learners who have not completed assessments.',
            ];
        }

        if (($summary['guardian_email_coverage_percent'] ?? 100) < 70) {
            $items[] = [
                'priority' => 'low',
                'type' => 'data_quality',
                'title' => 'Improve guardian contact coverage',
                'description' => 'Collect guardian emails to enable automated performance reports.',
            ];
        }

        if (($insights['learners_improving_percent'] ?? 0) < 30 && ($summary['learners'] ?? 0) > 5) {
            $items[] = [
                'priority' => 'medium',
                'type' => 'outcomes',
                'title' => 'Low improvement rate across cohort',
                'description' => 'Only '.$insights['learners_improving_percent'].'% of learners show score improvement. Review teaching strategies.',
            ];
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $supportLearners
     * @param  list<array<string, mixed>>  $weakCategories
     */
    private function teacherActionItems(array $insights, array $supportLearners, array $weakCategories): array
    {
        $items = [];

        foreach (array_slice($supportLearners, 0, 3) as $learner) {
            $items[] = [
                'priority' => 'high',
                'type' => 'learner_support',
                'title' => "Support {$learner['name']}",
                'description' => "Average {$learner['average_percent']}% ({$learner['competency_level']}). Consider one-on-one review.",
            ];
        }

        foreach (array_slice($weakCategories, 0, 2) as $cat) {
            $items[] = [
                'priority' => 'medium',
                'type' => 'class_focus',
                'title' => "Class-wide focus: {$cat['label']}",
                'description' => "Class average in this competency area is {$cat['average_percent']}%. Plan a revision session.",
            ];
        }

        if (($insights['learners_improving_percent'] ?? 0) >= 50) {
            $items[] = [
                'priority' => 'low',
                'type' => 'positive',
                'title' => 'Strong class momentum',
                'description' => $insights['learners_improving_percent'].'% of learners are improving. Keep current approach.',
            ];
        }

        return $items;
    }

    private function adminActionItems(int $attempts30d, int $studentCount, array $inclusion = []): array
    {
        $items = [];
        $engagementRate = $studentCount > 0
            ? round(($attempts30d / max($studentCount, 1)), 1)
            : 0;

        if ($engagementRate < 2) {
            $items[] = [
                'priority' => 'medium',
                'type' => 'engagement',
                'title' => 'Low platform engagement',
                'description' => "Only {$attempts30d} attempts in 30 days across {$studentCount} students. Consider outreach campaigns.",
            ];
        }

        $genderRate = $inclusion['gender_reporting']['reporting_rate_percent'] ?? 100;
        if ($genderRate < 60 && $studentCount > 0) {
            $items[] = [
                'priority' => 'high',
                'type' => 'inclusion',
                'title' => 'Improve gender data coverage',
                'description' => "Only {$genderRate}% of learners have gender recorded. Collect gender data for equity reporting.",
            ];
        }

        $items[] = [
            'priority' => 'low',
            'type' => 'monitoring',
            'title' => 'Review institution onboarding',
            'description' => 'Ensure new institutions have learners, teachers, and assessments configured.',
        ];

        return $items;
    }
}
