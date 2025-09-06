<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\Subject;
use Illuminate\Http\Request;

class AssessmentController extends Controller
{
    /**
     * Get list of all assessments that have questions (for practice)
     */
    public function index(Request $request)
    {
        $query = Assessment::with(['subject', 'creator.institution'])
            ->whereHas('questions'); // Only return assessments that have questions

        // Apply search filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('paper_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        if ($request->has('exam_body')) {
            $query->where('exam_body', $request->exam_body);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $assessments = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $assessments
        ]);
    }

    /**
     * Get assessment details
     */
    public function show(Request $request, Assessment $assessment)
    {
        // Check if assessment has questions
        if (!$assessment->questions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Assessment not found or has no questions'
            ], 404);
        }

        $assessment->load([
            'subject',
            'creator.institution',
            'sections.questions.media',
            'questions.media'
        ]);

        return response()->json([
            'success' => true,
            'data' => $assessment
        ]);
    }

    /**
     * Get assessments that a student has attempted
     */
    public function myAssessments(Request $request)
    {
        $user = $request->user();

        if (!$user->isStudent()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $query = Assessment::with(['subject', 'creator.institution'])
            ->whereHas('attempts', function ($q) use ($user) {
                $q->where('student_id', $user->id);
            })
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('paper_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        if ($request->has('exam_body')) {
            $query->where('exam_body', $request->exam_body);
        }

        $assessments = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $assessments
        ]);
    }

    /**
     * Get assessments for an institution (institution users only)
     */
    public function institutionAssessments(Request $request)
    {
        $user = $request->user();

        if (!$user->isInstitution()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $query = Assessment::whereHas('creator', function ($q) use ($user) {
            $q->where('institution_id', $user->institution_id);
        })
            ->with(['subject', 'creator', 'sections', 'questions'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->where('year', '>=', now()->year - 1);
            } elseif ($request->status === 'archived') {
                $query->where('year', '<', now()->year - 1);
            }
        }

        $assessments = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $assessments
        ]);
    }
}