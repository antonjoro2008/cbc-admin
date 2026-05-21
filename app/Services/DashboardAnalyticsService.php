<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Classroom;
use App\Models\Institution;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\TokenTransaction;
use App\Models\TokenUsage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    private const CACHE_TTL_SECONDS = 300;

    private const ADMIN_CACHE_KEY = 'dashboard.analytics.admin.v4';

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
        Cache::forget(self::ADMIN_CACHE_KEY);
        Cache::forget('dashboard.analytics.platform_learners');

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
        $wallet = $student->getEffectiveWallet();
        $historyAttempts = $attempts->sortByDesc('completed_at')->take(20);

        return [
            'profile' => [
                'student_id' => $student->id,
                'name' => $student->name,
                'grade_level' => $student->grade_level,
                'institution_id' => $student->institution_id,
                'is_individual' => $student->institution_id === null,
            ],
            'overview' => [
                'token_balance' => (float) ($wallet->balance ?? 0),
                'minutes_balance' => (float) ($wallet->available_minutes ?? 0),
                'average_percent' => $averagePercent,
                'competency_level' => $this->cohortAnalytics->competencyDescriptor($averagePercent),
                'total_completed_attempts' => $attempts->count(),
                'total_assessments_taken' => $attempts->count(),
                'progress_counter' => [
                    'completed' => $attempts->count(),
                    'label' => $attempts->count().' assessments completed',
                ],
                'distinct_assessments' => $attempts->pluck('assessment_id')->unique()->count(),
                'improvement_trend' => $trend['direction'],
                'improvement_delta_percent' => $trend['delta'],
                'last_activity_at' => $attempts->last()?->completed_at?->toIso8601String(),
                'active_days_last_30' => $this->distinctActiveDays($attempts, 30),
            ],
            'assessment_history' => $historyAttempts->map(fn (AssessmentAttempt $attempt) => [
                'attempt_id' => $attempt->id,
                'assessment_id' => $attempt->assessment_id,
                'assessment_name' => $attempt->assessment?->title ?? 'Assessment',
                'score_percent' => $this->attemptPercent($attempt),
                'date_taken' => $attempt->completed_at?->toIso8601String(),
                'subject' => $attempt->assessment?->subject?->name,
            ])->values()->all(),
            'competency_distribution' => $competencyDistribution,
            'charts' => [
                'performance_over_time' => $this->performanceOverTimeChart($attemptPercents),
                'score_trend_last_10' => $this->performanceOverTimeChart(array_slice($attemptPercents, -10)),
                'activity_last_14_days' => $this->activityChartForStudent($student->id),
                'subject_performance' => $this->barChartFromStats($subjectStats),
                'category_performance' => $this->barChartFromStats($categoryStats),
            ],
            'strengths' => $strengthsFormatted,
            'areas_for_improvement' => $weaknessesFormatted,
            'subject_breakdown' => array_values($subjectStats),
            'category_breakdown' => array_values($categoryStats),
            'recent_performance' => $this->recentAttemptSummaries($historyAttempts),
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
        $totalAttempts = AssessmentAttempt::whereIn('student_id', $studentIds)->count();
        $completedCount = $attempts->count();
        $completionRate = $totalAttempts > 0
            ? round(($completedCount / $totalAttempts) * 100, 1)
            : 0.0;

        return [
            'institution' => Institution::find($institutionId, ['id', 'name', 'motto', 'theme_color']),
            'summary' => array_merge($extras['summary'], [
                'total_completed_attempts' => $completedCount,
                'total_attempts' => $totalAttempts,
                'completion_rate_percent' => $completionRate,
                'average_school_score_percent' => $cohort['insights']['average_percent'] ?? 0,
            ]),
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
            'student_roster' => $this->institutionStudentRoster($studentIds, $attempts),
            'subject_breakdown' => array_values($subjectPerformance),
            'assessment_usage' => $this->assessmentUsageForStudentIds($studentIds),
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
        $totalAttempts = AssessmentAttempt::whereIn('student_id', $studentIds)->count();
        $completedCount = $attempts->count();
        $completionRate = $totalAttempts > 0
            ? round(($completedCount / $totalAttempts) * 100, 1)
            : 0.0;
        $rankedLearners = collect($learnerSummaries['all'])
            ->sortByDesc('average_percent')
            ->values()
            ->all();

        return [
            'classroom' => [
                'id' => $classroom?->id,
                'name' => $classroom?->name,
                'grade_level' => $classroom?->grade_level,
                'student_count' => $cohort['students']->count(),
                'learners_in_class' => $cohort['students']->count(),
            ],
            'overview' => [
                'learners_in_class' => $cohort['students']->count(),
                'class_average_score_percent' => $cohort['insights']['average_percent'] ?? 0,
                'class_average_level' => $cohort['insights']['average_level'] ?? '—',
                'completion_rate_percent' => $completionRate,
                'total_completed_attempts' => $completedCount,
            ],
            'students' => $cohort['students'],
            'insights' => $cohort['insights'],
            'inclusion_metrics' => $cohort['inclusion_metrics'],
            'competency_distribution' => $this->competencyDistribution($this->mapAttemptPercents($attempts)),
            'charts' => [
                'activity_last_14_days' => $this->activityChartForStudents($studentIds->all()),
                'learner_ranking' => [
                    'labels' => array_column($rankedLearners, 'name'),
                    'values' => array_column($rankedLearners, 'average_percent'),
                ],
                'subject_performance' => $this->barChartFromStats($subjectStats),
                'category_performance' => $this->barChartFromStats($categoryStats),
            ],
            'learner_performance_table' => array_map(fn (array $row): array => [
                'name' => $row['name'],
                'average_score_percent' => $row['average_percent'],
                'assessments_completed' => $row['completed_attempts'],
            ], $rankedLearners),
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
        return app(ParentDashboardService::class)->build($parent);
    }

    /**
     * Platform-wide analytics for admin users.
     */
    public function adminAnalytics(): array
    {
        if ($this->adminAnalyticsMemory !== null) {
            return $this->adminAnalyticsMemory;
        }

        $cached = Cache::get(self::ADMIN_CACHE_KEY);

        if ($cached !== null && is_array($cached) && $this->isCompleteAdminPayload($cached)) {
            return $this->adminAnalyticsMemory = $cached;
        }

        Cache::forget('dashboard.analytics.admin');
        Cache::forget(self::ADMIN_CACHE_KEY);

        $fresh = $this->computeAdminAnalytics();
        Cache::put(self::ADMIN_CACHE_KEY, $fresh, self::CACHE_TTL_SECONDS);

        return $this->adminAnalyticsMemory = $fresh;
    }

    /**
     * Safe access to a platform admin chart (handles stale or partial cache).
     *
     * @return array{labels: list<string>, values: list<int|float>}
     */
    public function adminChart(string $key): array
    {
        $charts = $this->adminAnalytics()['charts'] ?? [];

        return $charts[$key] ?? ['labels' => [], 'values' => []];
    }

    /**
     * Detect payloads cached before newer dashboard fields were added.
     */
    private function isCompleteAdminPayload(array $data): bool
    {
        return isset(
            $data['charts']['user_growth_over_time'],
            $data['charts']['tokens_purchased_vs_used'],
            $data['overview']['total_tokens_purchased'],
            $data['overview']['system_status'],
            $data['assessment_usage_by_school'],
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
        $institutionAccountCount = User::where('user_type', 'institution')->count();
        $teacherCount = User::where('user_type', 'teacher')->count();
        $parentCount = User::where('user_type', 'parent')->count();
        $adminCount = User::where('user_type', 'admin')->count();
        $otherAccountCount = User::query()
            ->where(function ($query): void {
                $query->whereNull('user_type')
                    ->orWhereNotIn('user_type', ['student', 'parent', 'teacher', 'institution', 'admin']);
            })
            ->count();
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
        $operations = $this->platformOperationsMetrics();

        $genderCohort = $inclusion['cohort_by_gender'] ?? [];
        $genderPerformance = $inclusion['performance_by_gender'] ?? [];

        return [
            'overview' => [
                'total_users' => User::count(),
                'total_students' => $studentCount,
                'institution_students' => $institutionStudents,
                'individual_students' => $individualStudents,
                'total_institutions' => $institutionCount,
                'total_institution_accounts' => $institutionAccountCount,
                'total_teachers' => $teacherCount,
                'total_parents' => $parentCount,
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
                'total_admins' => $adminCount,
                'total_other_accounts' => $otherAccountCount,
                'total_tokens_purchased' => $operations['total_tokens_purchased'],
                'total_tokens_used' => $operations['total_tokens_used'],
                'mpesa_payment_success_count' => $operations['mpesa_payment_success_count'],
                'mpesa_payment_failure_count' => $operations['mpesa_payment_failure_count'],
                'system_status' => $operations['system_status'],
                'system_status_label' => $operations['system_status_label'],
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
                'assessments_completed_per_period' => $activityChart,
                'user_growth_over_time' => $this->buildUserGrowthChart(),
                'tokens_purchased_vs_used' => $this->buildTokensPurchasedVsUsedChart(),
                'users_by_type' => [
                    'labels' => ['Learners', 'School accounts', 'Teachers', 'Parents', 'Admins'],
                    'values' => [
                        $studentCount,
                        $institutionAccountCount,
                        $teacherCount,
                        $parentCount,
                        $adminCount,
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
            'assessment_usage_by_school' => $this->platformAssessmentUsageBySchool(),
            'action_items' => $this->adminActionItems($attempts30d, $studentCount, $inclusion),
        ];
    }

    /**
     * Per-assessment analytics (assessment dashboard).
     *
     * @return array<string, mixed>
     */
    public function assessmentAnalyticsById(int $assessmentId): array
    {
        $assessment = Assessment::with('subject')->findOrFail($assessmentId);

        $attempts = AssessmentAttempt::query()
            ->where('assessment_id', $assessmentId)
            ->with('assessment')
            ->get();

        $this->warmAssessmentMarksForAttempts($attempts);

        $totalAttempts = $attempts->count();
        $completed = $attempts->whereNotNull('completed_at');
        $completedCount = $completed->count();
        $inProgress = $totalAttempts - $completedCount;

        $percents = [];
        $passCount = 0;
        foreach ($completed as $attempt) {
            $pct = $this->attemptPercent($attempt);
            if ($pct === null) {
                continue;
            }
            $percents[] = $pct;
            if ($pct >= 50) {
                $passCount++;
            }
        }

        $avgPercent = $percents !== [] ? round(collect($percents)->avg(), 2) : 0.0;
        $failCount = count($percents) - $passCount;
        $completionRate = $totalAttempts > 0
            ? round(($completedCount / $totalAttempts) * 100, 1)
            : 0.0;

        return [
            'assessment' => [
                'id' => $assessment->id,
                'name' => $assessment->title,
                'title' => $assessment->title,
                'subject' => $assessment->subject?->name,
                'status' => $assessment->status,
            ],
            'overview' => [
                'total_attempts' => $totalAttempts,
                'completed_attempts' => $completedCount,
                'in_progress_attempts' => $inProgress,
                'average_score_percent' => $avgPercent,
                'completion_rate_percent' => $completionRate,
                'pass_count' => $passCount,
                'fail_count' => $failCount,
                'difficulty_label' => $this->assessmentDifficultyLabel($avgPercent, $completionRate),
            ],
            'charts' => [
                'pass_vs_fail' => [
                    'labels' => ['Pass (≥50%)', 'Fail (<50%)'],
                    'values' => [$passCount, max(0, $failCount)],
                ],
                'completion_vs_dropout' => [
                    'labels' => ['Completed', 'In progress / dropped'],
                    'values' => [$completedCount, $inProgress],
                ],
            ],
        ];
    }

    /**
     * Platform operations: tokens, M-PESA, system health.
     *
     * @return array<string, mixed>
     */
    public function platformOperationsMetrics(): array
    {
        $tokensPurchased = (float) TokenTransaction::query()
            ->where('transaction_type', 'credit')
            ->sum('tokens');

        if ($tokensPurchased <= 0) {
            $tokensPurchased = (float) Payment::query()
                ->where('status', 'successful')
                ->sum('tokens');
        }

        $tokensUsed = (float) TokenUsage::query()->sum('tokens_used');

        $status = $this->resolveSystemStatus();

        return [
            'total_tokens_purchased' => round($tokensPurchased, 2),
            'total_tokens_used' => round($tokensUsed, 2),
            'mpesa_payment_success_count' => Payment::query()
                ->where('channel', 'mpesa')
                ->where('status', 'successful')
                ->count(),
            'mpesa_payment_failure_count' => Payment::query()
                ->where('channel', 'mpesa')
                ->where('status', 'failed')
                ->count(),
            'system_status' => $status['status'],
            'system_status_label' => $status['label'],
        ];
    }

    /**
     * Assessment attempt counts grouped by assessment for a cohort of students.
     *
     * @param  Collection<int, int|string>|\Illuminate\Support\Collection<int, int|string>  $studentIds
     * @return list<array<string, mixed>>
     */
    public function assessmentUsageForStudentIds($studentIds): array
    {
        $studentIds = collect($studentIds)->filter()->unique();
        if ($studentIds->isEmpty()) {
            return [];
        }

        $rows = AssessmentAttempt::query()
            ->whereIn('student_id', $studentIds)
            ->with('assessment:id,title')
            ->get()
            ->groupBy('assessment_id');

        $usage = [];
        foreach ($rows as $assessmentId => $group) {
            $completed = $group->whereNotNull('completed_at');
            $percents = [];
            foreach ($completed as $attempt) {
                $p = $this->attemptPercent($attempt);
                if ($p !== null) {
                    $percents[] = $p;
                }
            }

            $usage[] = [
                'assessment_id' => (int) $assessmentId,
                'assessment_name' => $group->first()->assessment?->title ?? 'Assessment',
                'total_attempts' => $group->count(),
                'completed_attempts' => $completed->count(),
                'average_score_percent' => $percents !== [] ? round(collect($percents)->avg(), 2) : null,
            ];
        }

        usort($usage, fn ($a, $b) => $b['completed_attempts'] <=> $a['completed_attempts']);

        return $usage;
    }

    /**
     * Assessment usage summary per school (platform master view).
     *
     * @return list<array<string, mixed>>
     */
    private function platformAssessmentUsageBySchool(): array
    {
        $table = [];
        foreach (Institution::orderBy('name')->get(['id', 'name']) as $institution) {
            $studentIds = User::query()
                ->where('institution_id', $institution->id)
                ->where('user_type', 'student')
                ->pluck('id');

            $usage = $this->assessmentUsageForStudentIds($studentIds);
            $table[] = [
                'school_id' => $institution->id,
                'school_name' => $institution->name,
                'distinct_assessments_used' => count($usage),
                'total_completed_attempts' => collect($usage)->sum('completed_attempts'),
                'assessments' => array_slice($usage, 0, 10),
            ];
        }

        return $table;
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildUserGrowthChart(int $months = 6): array
    {
        $labels = [];
        $values = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = Carbon::now()->subMonths($i)->startOfMonth();
            $end = (clone $start)->endOfMonth();
            $labels[] = $start->format('M Y');
            $values[] = User::query()
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array{labels: list<string>, values: list<float|int>}
     */
    private function buildTokensPurchasedVsUsedChart(): array
    {
        $ops = $this->platformOperationsMetrics();

        return [
            'labels' => ['Tokens purchased', 'Tokens used'],
            'values' => [$ops['total_tokens_purchased'], $ops['total_tokens_used']],
        ];
    }

    /**
     * @return array{status: string, label: string}
     */
    private function resolveSystemStatus(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'online', 'label' => 'Online'];
        } catch (\Throwable) {
            return ['status' => 'offline', 'label' => 'Offline'];
        }
    }

    private function assessmentDifficultyLabel(float $avgPercent, float $completionRate): string
    {
        if ($avgPercent < 40 || $completionRate < 30) {
            return 'High difficulty';
        }

        if ($avgPercent < 55 || $completionRate < 50) {
            return 'Moderate difficulty';
        }

        if ($avgPercent >= 75 && $completionRate >= 70) {
            return 'Accessible';
        }

        return 'Standard difficulty';
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

    /**
     * Analytics for a specific teacher (classroom-scoped).
     *
     * @return array<string, mixed>
     */
    public function teacherAnalyticsById(int $teacherId): array
    {
        $teacher = User::where('id', $teacherId)->where('user_type', 'teacher')->firstOrFail();

        return $this->teacherAnalytics($teacher);
    }

    /**
     * Analytics for all assessments under a subject.
     *
     * @return array<string, mixed>
     */
    public function subjectAnalyticsById(int $subjectId): array
    {
        return Cache::remember(
            "dashboard.analytics.subject.{$subjectId}",
            self::CACHE_TTL_SECONDS,
            fn () => $this->computeSubjectAnalyticsById($subjectId),
        );
    }

    /**
     * All platform learners with performance summaries (institution and individual).
     *
     * @return list<array<string, mixed>>
     */
    public function platformLearnerRoster(): array
    {
        return Cache::remember(
            'dashboard.analytics.platform_learners',
            self::CACHE_TTL_SECONDS,
            fn () => $this->computePlatformLearnerRoster(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function computeSubjectAnalyticsById(int $subjectId): array
    {
        $subject = Subject::find($subjectId, ['id', 'name', 'code']);
        $assessmentIds = Assessment::query()->where('subject_id', $subjectId)->pluck('id');

        if ($assessmentIds->isEmpty()) {
            return [
                'subject' => $subject?->only(['id', 'name', 'code']),
                'overview' => [
                    'total_assessments' => 0,
                    'total_attempts' => 0,
                    'completed_attempts' => 0,
                    'average_score_percent' => 0,
                    'completion_rate_percent' => 0,
                ],
                'charts' => [
                    'assessment_performance' => ['labels' => [], 'values' => []],
                    'activity_last_14_days' => ['labels' => [], 'values' => []],
                    'category_performance' => ['labels' => [], 'values' => []],
                ],
                'category_breakdown' => [],
                'assessment_breakdown' => [],
            ];
        }

        $attempts = AssessmentAttempt::query()
            ->whereIn('assessment_id', $assessmentIds)
            ->whereNotNull('completed_at')
            ->with(['assessment:id,title,subject_id', 'student:id,name'])
            ->orderBy('completed_at', 'desc')
            ->limit(1000)
            ->get();

        $studentIds = $attempts->pluck('student_id')->unique();
        $totalAttempts = AssessmentAttempt::query()->whereIn('assessment_id', $assessmentIds)->count();
        $completedCount = $attempts->count();
        $percents = $this->mapAttemptPercents($attempts);
        $avgPercent = $this->averageFromPercents($percents);
        $completionRate = $totalAttempts > 0
            ? round(($completedCount / $totalAttempts) * 100, 1)
            : 0.0;

        $byAssessment = [];
        foreach ($attempts->groupBy('assessment_id') as $assessmentId => $group) {
            $groupPercents = $this->mapAttemptPercents($group);
            $byAssessment[] = [
                'assessment_id' => $assessmentId,
                'assessment_name' => $group->first()?->assessment?->title ?? 'Assessment',
                'completed_attempts' => $group->count(),
                'average_percent' => $this->averageFromPercents($groupPercents),
            ];
        }

        usort($byAssessment, fn (array $a, array $b): int => ($b['completed_attempts'] ?? 0) <=> ($a['completed_attempts'] ?? 0));

        $markedAnswers = AttemptAnswer::query()
            ->whereHas('attempt', fn ($q) => $q
                ->whereIn('assessment_id', $assessmentIds)
                ->whereNotNull('completed_at'))
            ->whereHas('feedback')
            ->with(['question:id,category_tag,marks'])
            ->get(['id', 'attempt_id', 'question_id', 'marks_awarded']);

        $categoryStats = $this->aggregateCategoryPerformance($markedAnswers);

        return [
            'subject' => $subject?->only(['id', 'name', 'code']),
            'overview' => [
                'total_assessments' => $assessmentIds->count(),
                'total_attempts' => $totalAttempts,
                'completed_attempts' => $completedCount,
                'average_score_percent' => $avgPercent,
                'completion_rate_percent' => $completionRate,
                'distinct_learners' => $studentIds->count(),
            ],
            'competency_distribution' => $this->competencyDistribution($percents),
            'charts' => [
                'assessment_performance' => [
                    'labels' => array_column($byAssessment, 'assessment_name'),
                    'values' => array_column($byAssessment, 'average_percent'),
                ],
                'activity_last_14_days' => $this->activityChartForStudents($studentIds->all()),
                'category_performance' => $this->barChartFromStats($categoryStats),
            ],
            'category_breakdown' => array_values($categoryStats),
            'assessment_breakdown' => $byAssessment,
        ];
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
    private function normalizeCategoryTag(?string $tag): string
    {
        $tag = trim((string) $tag);

        if ($tag === '' || strcasecmp($tag, 'Uncategorized') === 0) {
            return 'General';
        }

        return $tag;
    }

    private function aggregateCategoryPerformance(Collection $answers): array
    {
        $grouped = [];
        foreach ($answers as $answer) {
            $tag = $this->normalizeCategoryTag($answer->question?->category_tag);
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
     * @return list<array<string, mixed>>
     */
    private function computePlatformLearnerRoster(): array
    {
        $studentIds = User::query()
            ->where('user_type', 'student')
            ->pluck('id');

        if ($studentIds->isEmpty()) {
            return [];
        }

        $attempts = AssessmentAttempt::query()
            ->whereIn('student_id', $studentIds)
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'desc')
            ->limit(5000)
            ->get();

        $since = Carbon::now()->subDays(30)->startOfDay();
        $inactiveIds = User::query()
            ->where('user_type', 'student')
            ->whereDoesntHave('assessmentAttempts', fn ($q) => $q
                ->whereNotNull('completed_at')
                ->where('completed_at', '>=', $since))
            ->pluck('id')
            ->flip();

        $byStudent = [];
        foreach ($attempts as $attempt) {
            $percent = $this->attemptPercent($attempt);
            if ($percent === null) {
                continue;
            }
            $byStudent[$attempt->student_id]['percents'][] = $percent;
            $byStudent[$attempt->student_id]['attempts'] = ($byStudent[$attempt->student_id]['attempts'] ?? 0) + 1;
        }

        $students = User::query()
            ->with(['institution:id,name', 'classroom:id,name'])
            ->whereIn('id', $studentIds)
            ->get([
                'id',
                'name',
                'email',
                'phone_number',
                'admission_number',
                'grade_level',
                'gender',
                'classroom_id',
                'institution_id',
            ]);

        $roster = [];
        foreach ($students as $student) {
            $row = $byStudent[$student->id] ?? null;
            $avg = $row ? round(collect($row['percents'])->avg(), 2) : null;
            $attemptsCount = (int) ($row['attempts'] ?? 0);
            $genderKey = ($student->gender && in_array($student->gender, User::GENDER_VALUES, true))
                ? $student->gender
                : 'unspecified';

            if ($inactiveIds->has($student->id)) {
                $status = 'Inactive (30d)';
            } elseif ($attemptsCount === 0) {
                $status = 'No attempts yet';
            } elseif ($avg !== null && $avg < 50) {
                $status = 'Needs support';
            } else {
                $status = 'On track';
            }

            $roster[] = [
                'student_id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
                'phone_number' => $student->phone_number,
                'admission_number' => $student->admission_number,
                'grade_level' => $student->grade_level,
                'classroom_name' => $student->classroom?->name,
                'gender' => InstitutionLearnerAnalyticsService::genderLabel($genderKey),
                'institution_id' => $student->institution_id,
                'institution_name' => $student->institution_id
                    ? ($student->institution?->name ?? '—')
                    : '—',
                'completed_attempts' => $attemptsCount,
                'average_percent' => $avg,
                'competency_level' => $avg !== null
                    ? $this->cohortAnalytics->competencyDescriptor($avg)
                    : '—',
                'status' => $status,
            ];
        }

        usort($roster, function (array $a, array $b): int {
            $avgA = $a['average_percent'];
            $avgB = $b['average_percent'];

            if ($avgA === null && $avgB === null) {
                return strcasecmp($a['name'], $b['name']);
            }

            if ($avgA === null) {
                return 1;
            }

            if ($avgB === null) {
                return -1;
            }

            return $avgB <=> $avgA;
        });

        return $roster;
    }

    /**
     * Full learner roster for an institution with attempt summaries where available.
     *
     * @param  Collection<int, int|string>|array<int, int|string>  $studentIds
     * @param  Collection<int, AssessmentAttempt>  $attempts
     * @return list<array<string, mixed>>
     */
    private function institutionStudentRoster($studentIds, Collection $attempts): array
    {
        $studentIds = collect($studentIds)->filter()->unique()->values();
        if ($studentIds->isEmpty()) {
            return [];
        }

        $byStudent = [];
        foreach ($attempts as $attempt) {
            $percent = $this->attemptPercent($attempt);
            if ($percent === null) {
                continue;
            }
            $byStudent[$attempt->student_id]['percents'][] = $percent;
            $byStudent[$attempt->student_id]['attempts'] = ($byStudent[$attempt->student_id]['attempts'] ?? 0) + 1;
        }

        $students = User::query()
            ->with('classroom:id,name')
            ->whereIn('id', $studentIds)
            ->orderBy('name')
            ->get(['id', 'name', 'admission_number', 'grade_level', 'gender', 'classroom_id']);

        $roster = [];
        foreach ($students as $student) {
            $row = $byStudent[$student->id] ?? null;
            $avg = $row ? round(collect($row['percents'])->avg(), 2) : null;
            $genderKey = ($student->gender && in_array($student->gender, User::GENDER_VALUES, true))
                ? $student->gender
                : 'unspecified';

            $roster[] = [
                'student_id' => $student->id,
                'name' => $student->name,
                'admission_number' => $student->admission_number,
                'grade_level' => $student->grade_level,
                'classroom_name' => $student->classroom?->name,
                'gender' => InstitutionLearnerAnalyticsService::genderLabel($genderKey),
                'completed_attempts' => $row['attempts'] ?? 0,
                'average_percent' => $avg,
                'competency_level' => $avg !== null
                    ? $this->cohortAnalytics->competencyDescriptor($avg)
                    : '—',
            ];
        }

        return $roster;
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
