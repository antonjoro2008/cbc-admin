<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAttempt;
use App\Services\AssessmentAttemptSummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssessmentAttemptController extends Controller
{
    public function __construct(
        private readonly AssessmentAttemptSummaryService $attemptSummaries,
    ) {}

    /**
     * List assessment attempts visible to the authenticated user.
     * Optional ?student_id= for teacher / parent / institution viewing one learner.
     */
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $studentId = $request->filled('student_id') ? (int) $request->student_id : null;
        $perPage = min(50, max(5, (int) $request->get('per_page', 20)));

        $paginator = $this->attemptSummaries->listAttempts($viewer, $studentId, $perPage);

        $assessmentIds = $paginator->getCollection()->pluck('assessment_id')->unique()->filter()->all();
        $marksMap = $this->attemptSummaries->assessmentTotalMarksMap($assessmentIds);

        $items = $paginator->getCollection()
            ->map(fn (AssessmentAttempt $attempt) => $this->attemptSummaries->formatAttemptListItem($attempt, $marksMap))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => [
                'attempts' => $items,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        ]);
    }

    /**
     * Full summary for a past attempt (same shape as submit response).
     */
    public function show(Request $request, int $attemptId): JsonResponse
    {
        $attempt = AssessmentAttempt::with('student')->find($attemptId);

        if (! $attempt) {
            return response()->json([
                'success' => false,
                'message' => 'Attempt not found',
            ], 404);
        }

        if (! $this->attemptSummaries->canViewAttempt($request->user(), $attempt)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (! $attempt->completed_at) {
            return response()->json([
                'success' => false,
                'message' => 'This attempt is not completed yet',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->attemptSummaries->buildSummaryPayload($attempt),
        ]);
    }
}
