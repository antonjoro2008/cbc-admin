<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InstitutionClassroomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isInstitution()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $query = Classroom::where('institution_id', $user->institution_id)->orderBy('grade_level')->orderBy('name');

        if ($request->filled('grade_level')) {
            $query->where('grade_level', $request->grade_level);
        }

        return response()->json([
            'success' => true,
            'data' => $query->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isInstitution()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'grade_level' => 'required|string|max:50',
            'teacher_user_id' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->filled('teacher_user_id')) {
            $teacher = \App\Models\User::where('id', $request->teacher_user_id)
                ->where('institution_id', $user->institution_id)
                ->where('user_type', 'institution')
                ->first();
            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid teacher for this institution.',
                ], 422);
            }
        }

        $classroom = Classroom::create([
            'institution_id' => $user->institution_id,
            'teacher_user_id' => $request->teacher_user_id,
            'name' => $request->name,
            'grade_level' => $request->grade_level,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Classroom created',
            'data' => $classroom,
        ], 201);
    }
}
