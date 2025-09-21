<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    /**
     * Get list of all subjects
     */
    public function index(Request $request)
    {
        $query = Subject::query();

        // Apply search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $subjects = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $subjects
        ]);
    }

    /**
     * Get subject details with assessment count
     */
    public function show(Request $request, Subject $subject)
    {
        // Get assessment count for this subject
        $assessmentCount = $subject->assessments()
            ->where('status', 1) // Only count active assessments
            ->whereHas('questions') // Only count assessments with questions
            ->count();

        $subject->assessment_count = $assessmentCount;

        return response()->json([
            'success' => true,
            'data' => $subject
        ]);
    }
}
