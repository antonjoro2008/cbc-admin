<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class InstitutionTeacherController extends Controller
{
    public function index(): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isInstitution()) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $teachers = User::where('institution_id', $user->institution_id)
            ->where('user_type', 'teacher')
            ->with('classroom')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $teachers]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isInstitution()) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15|unique:users,phone_number',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'classroom_id' => [
                'nullable',
                'integer',
                Rule::exists('classrooms', 'id')->where(fn ($q) => $q->where('institution_id', $user->institution_id)),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $teacher = User::create([
            'institution_id' => $user->institution_id,
            'classroom_id' => $request->classroom_id,
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => 'teacher',
            'grade_level' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Teacher created successfully', 'data' => $teacher], 201);
    }

    public function update(Request $request, User $teacher): JsonResponse
    {
        $user = Auth::user();

        if (!$user->isInstitution()) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        if ($teacher->institution_id !== $user->institution_id || $teacher->user_type !== 'teacher') {
            return response()->json(['success' => false, 'message' => 'Teacher not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15|unique:users,phone_number,' . $teacher->id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $teacher->id,
            'classroom_id' => [
                'nullable',
                'integer',
                Rule::exists('classrooms', 'id')->where(fn ($q) => $q->where('institution_id', $user->institution_id)),
            ],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $teacher->update([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'classroom_id' => $request->classroom_id,
        ]);

        return response()->json(['success' => true, 'message' => 'Teacher updated', 'data' => $teacher->fresh('classroom')]);
    }
}

