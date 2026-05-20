<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardAnalyticsService;
use App\Services\DashboardDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    public function __construct(
        private readonly DashboardAnalyticsService $analytics,
        private readonly DashboardDataService $dashboardData,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $teacher = Auth::user();

        if (! $teacher->isTeacher()) {
            return response()->json(['success' => false, 'message' => 'Teacher access required'], 403);
        }

        $analytics = $this->analytics->teacherAnalytics($teacher);

        return response()->json([
            'success' => true,
            'data' => array_merge($analytics, [
                'assessment_stats' => $this->dashboardData->getAssessmentStats($teacher),
                'recent_attempts' => $this->dashboardData->getRecentAttempts($teacher),
            ]),
        ]);
    }
}
