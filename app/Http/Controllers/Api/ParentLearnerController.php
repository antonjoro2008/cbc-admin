<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentLearner;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ParentLearnerController extends Controller
{
    /**
     * Get all learners for the authenticated parent.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only parents can view their learners.'
            ], 403);
        }

        $learners = $user->parentLearners()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $learners
        ]);
    }

    /**
     * Add a single learner for the authenticated parent.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only parents can add learners.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'grade_level' => 'nullable|string|max:45',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $learner = $user->parentLearners()->create([
            'name' => $request->name,
            'grade_level' => $request->grade_level,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Learner added successfully',
            'data' => $learner
        ], 201);
    }

    /**
     * Add multiple learners for the authenticated parent.
     */
    public function storeMultiple(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only parents can add learners.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'learners' => 'required|array|min:1',
            'learners.*.name' => 'required|string|max:255',
            'learners.*.grade_level' => 'nullable|string|max:45',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $learners = [];
        foreach ($request->learners as $learnerData) {
            $learners[] = $user->parentLearners()->create([
                'name' => $learnerData['name'],
                'grade_level' => $learnerData['grade_level'] ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => count($learners) . ' learners added successfully',
            'data' => $learners
        ], 201);
    }

    /**
     * Update a specific learner.
     */
    public function update(Request $request, ParentLearner $parentLearner): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only parents can update learners.'
            ], 403);
        }

        // Check if the learner belongs to the authenticated parent
        if ($parentLearner->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You can only update your own learners.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'grade_level' => 'nullable|string|max:45',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $parentLearner->update([
            'name' => $request->name,
            'grade_level' => $request->grade_level,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Learner updated successfully',
            'data' => $parentLearner
        ]);
    }

    /**
     * Delete a specific learner.
     */
    public function destroy(ParentLearner $parentLearner): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only parents can delete learners.'
            ], 403);
        }

        // Check if the learner belongs to the authenticated parent
        if ($parentLearner->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You can only delete your own learners.'
            ], 403);
        }

        $parentLearner->delete();

        return response()->json([
            'success' => true,
            'message' => 'Learner deleted successfully'
        ]);
    }

    /**
     * Get a specific learner.
     */
    public function show(ParentLearner $parentLearner): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isParent()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only parents can view learners.'
            ], 403);
        }

        // Check if the learner belongs to the authenticated parent
        if ($parentLearner->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You can only view your own learners.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $parentLearner
        ]);
    }
}