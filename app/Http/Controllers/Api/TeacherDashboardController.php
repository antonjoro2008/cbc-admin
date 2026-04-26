<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\InstitutionLearnerAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    public function index(Request $request, InstitutionLearnerAnalyticsService $analytics): JsonResponse
    {
        $teacher = Auth::user();

        if (! $teacher->isTeacher()) {
            return response()->json(['success' => false, 'message' => 'Teacher access required'], 403);
        }

        if (! $teacher->institution_id || ! $teacher->classroom_id) {
            return response()->json([
                'success' => true,
                'data' => [
                    'classroom_id' => $teacher->classroom_id,
                    'students' => [],
                    'insights' => [
                        'average_percent' => 0,
                        'average_level' => '—',
                        'learners_improving_percent' => 0,
                    ],
                    'inclusion_metrics' => $analytics->emptyInclusionMetrics(0),
                ],
            ]);
        }

        $data = $analytics->learnerCohortAnalytics(
            $teacher->institution_id,
            $teacher->classroom_id,
            300,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'classroom_id' => $data['classroom_id'],
                'students' => $data['students'],
                'insights' => $data['insights'],
                'inclusion_metrics' => $data['inclusion_metrics'],
            ],
        ]);
    }
}
