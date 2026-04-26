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

class TeacherStudentController extends Controller
{
    /**
     * Teacher adds a learner into their own classroom only.
     */
    public function store(Request $request): JsonResponse
    {
        $teacher = Auth::user();

        if (!$teacher->isTeacher()) {
            return response()->json(['success' => false, 'message' => 'Access denied.'], 403);
        }

        if (!$teacher->institution_id) {
            return response()->json(['success' => false, 'message' => 'Teacher is not linked to an institution.'], 422);
        }

        if (!$teacher->classroom_id) {
            return response()->json(['success' => false, 'message' => 'Teacher is not assigned to a classroom.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'admission_number' => 'required|string|max:50|unique:users,admission_number',
            'email' => 'nullable|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'grade_level' => 'required|string|max:50',
            'guardian_email' => 'nullable|string|email|max:255',
            'guardian_phone' => 'nullable|string|max:50',
            'gender' => ['nullable', 'string', Rule::in(User::GENDER_VALUES)],
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $student = User::create([
            'institution_id' => $teacher->institution_id,
            'classroom_id' => $teacher->classroom_id,
            'name' => $request->name,
            'admission_number' => $request->admission_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => 'student',
            'grade_level' => $request->grade_level,
            'guardian_email' => $request->guardian_email,
            'guardian_phone' => $request->guardian_phone,
            'gender' => $request->filled('gender') ? $request->gender : null,
        ]);

        return response()->json(['success' => true, 'message' => 'Student created', 'data' => $student], 201);
    }
}

