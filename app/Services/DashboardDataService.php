<?php

namespace App\Services;

use App\Models\Assessment;
use App\Services\ParentDashboardService;
use App\Models\AssessmentAttempt;
use App\Models\AttemptAnswer;
use App\Models\Setting;
use App\Models\TokenTransaction;
use App\Models\TokenUsage;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DashboardDataService
{
    public function __construct(
        private readonly DashboardAnalyticsService $analytics,
    ) {}

    /**
     * Build the full dashboard payload for API responses (dashboard, login, register).
     *
     * @return array<string, mixed>
     */
    public function buildPayload(User $user, bool $includeSettings = false): array
    {
        $effectiveWallet = $user->getEffectiveWallet();

        $data = [
            'user_type' => $user->user_type,
            'token_balance' => $effectiveWallet->balance ?? 0,
            'minutes_balance' => $effectiveWallet->available_minutes ?? 0,
            'assessment_stats' => $this->getAssessmentStats($user),
            'recent_assessments' => $this->getRecentAssessments($user),
            'recent_attempts' => $this->getRecentAttempts($user),
            'analytics' => $this->analytics->buildForUser($user),
        ];

        if ($includeSettings) {
            $data['settings'] = [
                'tokens_per_shilling' => (float) Setting::getValue('tokens_per_shilling', 1.0),
                'minutes_per_token' => (float) Setting::getValue('minutes_per_token', 1.0),
            ];
        }

        return $data;
    }

    /**
     * Build the dashboard index response including user and institution details.
     *
     * @return array<string, mixed>
     */
    public function buildIndexPayload(User $user): array
    {
        $data = array_merge(
            ['user' => $user->load('institution', 'wallet')],
            $this->buildPayload($user),
        );

        if ($user->isInstitution() && $user->institution) {
            $data['institution'] = $user->institution;
        }

        return $data;
    }

    /**
     * @return array<string, int|float>
     */
    public function getAssessmentStats(User $user): array
    {
        if ($user->isStudent()) {
            return $this->studentAssessmentStats($user->id);
        }

        if ($user->isInstitution()) {
            return $this->institutionAssessmentStats($user->institution_id);
        }

        if ($user->isTeacher() && $user->institution_id) {
            return $this->institutionAssessmentStats(
                $user->institution_id,
                $user->classroom_id,
            );
        }

        if ($user->isAdmin()) {
            return $this->platformAssessmentStats();
        }

        if ($user->isParent()) {
            return app(ParentDashboardService::class)->householdAssessmentStats($user);
        }

        return $this->emptyAssessmentStats();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Assessment>
     */
    public function getRecentAssessments(User $user)
    {
        if ($user->isStudent() || $user->isParent()) {
            return Assessment::whereHas('attempts', fn ($q) => $q->where('student_id', $user->id))
                ->orWhere('created_by', $user->id)
                ->with(['subject', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        if ($user->isInstitution() || $user->isTeacher()) {
            $institutionId = $user->institution_id;

            return Assessment::whereHas('creator', fn ($q) => $q->where('institution_id', $institutionId))
                ->with(['subject', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return Assessment::with(['subject', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AssessmentAttempt>
     */
    public function getRecentAttempts(User $user)
    {
        if ($user->isStudent()) {
            return AssessmentAttempt::where('student_id', $user->id)
                ->with(['assessment.subject'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        if ($user->isInstitution() || $user->isTeacher()) {
            $query = AssessmentAttempt::whereHas('student', fn ($q) => $q->where('institution_id', $user->institution_id));

            if ($user->isTeacher() && $user->classroom_id) {
                $query->whereHas('student', fn ($q) => $q->where('classroom_id', $user->classroom_id));
            }

            return $query->with(['assessment.subject', 'student'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return AssessmentAttempt::with(['assessment.subject', 'student'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    public function getTokenHistory(User $user): LengthAwarePaginator
    {
        $effectiveWallet = $user->getEffectiveWallet();

        return TokenTransaction::where('wallet_id', $effectiveWallet->id ?? 0)
            ->with(['wallet.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    /**
     * @return array<string, int|float>
     */
    private function studentAssessmentStats(int $studentId): array
    {
        $baseQuery = AssessmentAttempt::where('student_id', $studentId);
        $totalAttempts = (clone $baseQuery)->count();
        $completedAttempts = (clone $baseQuery)->whereNotNull('completed_at')->count();
        $inProgressAttempts = (clone $baseQuery)->whereNull('completed_at')->count();

        return [
            'total_attempts' => $totalAttempts,
            'completed_attempts' => $completedAttempts,
            'in_progress_attempts' => $inProgressAttempts,
            'average_score' => $this->calculateAverageScore($studentId),
            'total_tokens_used' => (int) TokenUsage::whereHas('attempt', fn ($q) => $q->where('student_id', $studentId))
                ->sum('tokens_used'),
            'completion_rate' => $totalAttempts > 0
                ? round(($completedAttempts / $totalAttempts) * 100, 2)
                : 0,
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function institutionAssessmentStats(?int $institutionId, ?int $classroomId = null): array
    {
        if (! $institutionId) {
            return $this->emptyAssessmentStats();
        }

        $studentQuery = User::where('institution_id', $institutionId)->where('user_type', 'student');
        if ($classroomId) {
            $studentQuery->where('classroom_id', $classroomId);
        }
        $studentIds = $studentQuery->pluck('id');

        if ($studentIds->isEmpty()) {
            return $this->emptyAssessmentStats();
        }

        $baseQuery = AssessmentAttempt::whereIn('student_id', $studentIds);
        $totalAttempts = (clone $baseQuery)->count();
        $completedAttempts = (clone $baseQuery)->whereNotNull('completed_at')->count();
        $inProgressAttempts = (clone $baseQuery)->whereNull('completed_at')->count();

        $avgScores = [];
        foreach ((clone $baseQuery)->whereNotNull('completed_at')->with('assessment')->get() as $attempt) {
            $outOf = $attempt->assessment?->questions()->sum('marks');
            if ($outOf && $attempt->score !== null) {
                $avgScores[] = ((float) $attempt->score / (float) $outOf) * 100;
            }
        }

        return [
            'total_attempts' => $totalAttempts,
            'completed_attempts' => $completedAttempts,
            'in_progress_attempts' => $inProgressAttempts,
            'average_score' => $avgScores !== [] ? round(collect($avgScores)->avg(), 2) : 0,
            'total_tokens_used' => (int) TokenUsage::whereHas('attempt', fn ($q) => $q->whereIn('student_id', $studentIds))
                ->sum('tokens_used'),
            'completion_rate' => $totalAttempts > 0
                ? round(($completedAttempts / $totalAttempts) * 100, 2)
                : 0,
            'learner_count' => $studentIds->count(),
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function platformAssessmentStats(): array
    {
        $totalAttempts = AssessmentAttempt::count();
        $completedAttempts = AssessmentAttempt::whereNotNull('completed_at')->count();

        return [
            'total_attempts' => $totalAttempts,
            'completed_attempts' => $completedAttempts,
            'in_progress_attempts' => AssessmentAttempt::whereNull('completed_at')->count(),
            'average_score' => round((float) (AssessmentAttempt::whereNotNull('score')->avg('score') ?? 0), 2),
            'total_tokens_used' => (int) TokenUsage::sum('tokens_used'),
            'completion_rate' => $totalAttempts > 0
                ? round(($completedAttempts / $totalAttempts) * 100, 2)
                : 0,
        ];
    }

    /**
     * @return array<string, int|float>
     */
    private function emptyAssessmentStats(): array
    {
        return [
            'total_attempts' => 0,
            'completed_attempts' => 0,
            'in_progress_attempts' => 0,
            'average_score' => 0,
            'total_tokens_used' => 0,
            'completion_rate' => 0,
        ];
    }

    private function calculateAverageScore(int $studentId): float
    {
        $markedAttemptAnswers = AttemptAnswer::whereHas('attempt', fn ($q) => $q->where('student_id', $studentId))
            ->whereHas('feedback')
            ->with(['question', 'feedback']);

        $totalMarksAwarded = $markedAttemptAnswers->sum('marks_awarded');
        $totalPossibleMarks = $markedAttemptAnswers->get()->sum(fn ($aa) => $aa->question->marks);

        if ($totalPossibleMarks > 0) {
            return round(($totalMarksAwarded / $totalPossibleMarks) * 100, 2);
        }

        return 0;
    }
}
