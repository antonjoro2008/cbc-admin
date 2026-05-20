<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use App\Services\DashboardDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardDataService $dashboardData,
        private readonly DashboardAnalyticsService $analytics,
    ) {}

    /**
     * Get dashboard overview data with comprehensive role-specific analytics.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardData->buildIndexPayload($request->user()),
        ]);
    }

    /**
     * Get comprehensive analytics only (independent endpoint).
     */
    public function analytics(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->analytics->buildForUser($request->user()),
        ]);
    }

    /**
     * Drill-down analytics for a specific student (institution/teacher/admin).
     */
    public function studentAnalytics(Request $request, int $studentId): JsonResponse
    {
        $user = $request->user();
        $student = User::where('id', $studentId)->where('user_type', 'student')->firstOrFail();

        if (! $this->canViewStudentAnalytics($user, $student)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->analytics->studentAnalyticsById($studentId),
        ]);
    }

    /**
     * Drill-down analytics for a specific institution (admin only).
     */
    public function institutionAnalytics(Request $request, int $institutionId): JsonResponse
    {
        if (! $request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Admin access required'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->analytics->institutionAnalyticsById($institutionId),
        ]);
    }

    /**
     * Get token balance.
     */
    public function tokenBalance(Request $request): JsonResponse
    {
        $user = $request->user();
        $effectiveWallet = $user->getEffectiveWallet();

        return response()->json([
            'success' => true,
            'data' => [
                'user_type' => $user->user_type,
                'token_balance' => $effectiveWallet->balance ?? 0,
                'minutes_balance' => $effectiveWallet->available_minutes ?? 0,
                'wallet_id' => $effectiveWallet->id ?? null,
            ],
        ]);
    }

    /**
     * Get assessment statistics.
     */
    public function assessmentStats(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardData->getAssessmentStats($request->user()),
        ]);
    }

    /**
     * Get recent assessments.
     */
    public function recentAssessments(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardData->getRecentAssessments($request->user()),
        ]);
    }

    /**
     * Get token history.
     */
    public function tokenHistory(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->dashboardData->getTokenHistory($request->user()),
        ]);
    }

    private function canViewStudentAnalytics(User $viewer, User $student): bool
    {
        if ($viewer->isAdmin()) {
            return true;
        }

        if ($viewer->isStudent() && $viewer->id === $student->id) {
            return true;
        }

        if ($viewer->institution_id && $student->institution_id === $viewer->institution_id) {
            if ($viewer->isInstitution()) {
                return true;
            }

            if ($viewer->isTeacher() && $viewer->classroom_id === $student->classroom_id) {
                return true;
            }
        }

        return false;
    }
}
