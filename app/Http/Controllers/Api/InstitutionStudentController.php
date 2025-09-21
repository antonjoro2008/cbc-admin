<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
// Note: Excel import functionality requires Laravel Excel package
// Install with: composer require maatwebsite/excel
// use Maatwebsite\Excel\Facades\Excel;
// use Maatwebsite\Excel\Concerns\ToModel;
// use Maatwebsite\Excel\Concerns\WithHeadingRow;
// use Maatwebsite\Excel\Concerns\WithValidation;

class InstitutionStudentController extends Controller
{
    /**
     * Get all students for the authenticated institution.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->isInstitution()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only institution users can view their students.'
            ], 403);
        }

        $query = User::where('institution_id', $user->institution_id)
            ->where('user_type', 'student');

        // Add search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%")
                  ->orWhere('grade_level', 'like', "%{$search}%");
            });
        }

        // Add grade level filter
        if ($request->has('grade_level') && $request->grade_level) {
            $query->where('grade_level', $request->grade_level);
        }

        $students = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $students
        ]);
    }

    /**
     * Create a single student for the authenticated institution.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->isInstitution()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only institution users can add students.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'admission_number' => 'required|string|max:50|unique:users',
            'email' => 'nullable|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'grade_level' => 'required|string|max:50',
        ], [
            'name.required' => 'Student name is required.',
            'name.max' => 'Student name cannot exceed 255 characters.',
            'admission_number.required' => 'Admission number is required.',
            'admission_number.max' => 'Admission number cannot exceed 50 characters.',
            'admission_number.unique' => 'This admission number is already registered.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'grade_level.required' => 'Grade level is required.',
            'grade_level.max' => 'Grade level cannot exceed 50 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $student = User::create([
                'name' => $request->name,
                'admission_number' => $request->admission_number,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'institution_id' => $user->institution_id,
                'grade_level' => $request->grade_level,
                'user_type' => 'student',
                // Note: No wallet is created for institution students
                // They share the institution admin's wallet
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Student created successfully',
                'data' => $student
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create student. Please try again.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create multiple students for the authenticated institution.
     */
    public function storeMultiple(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->isInstitution()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only institution users can add students.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'students' => 'required|array|min:1|max:100',
            'students.*.name' => 'required|string|max:255',
            'students.*.admission_number' => 'required|string|max:50',
            'students.*.email' => 'nullable|string|email|max:255',
            'students.*.password' => ['required', Password::defaults()],
            'students.*.grade_level' => 'required|string|max:50',
        ], [
            'students.required' => 'Students data is required.',
            'students.array' => 'Students must be an array.',
            'students.min' => 'At least one student is required.',
            'students.max' => 'Maximum 100 students can be added at once.',
            'students.*.name.required' => 'Student name is required.',
            'students.*.name.max' => 'Student name cannot exceed 255 characters.',
            'students.*.admission_number.required' => 'Admission number is required.',
            'students.*.admission_number.max' => 'Admission number cannot exceed 50 characters.',
            'students.*.email.email' => 'Please provide a valid email address.',
            'students.*.password.required' => 'Password is required.',
            'students.*.grade_level.required' => 'Grade level is required.',
            'students.*.grade_level.max' => 'Grade level cannot exceed 50 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $createdStudents = [];
            $errors = [];

            foreach ($request->students as $index => $studentData) {
                // Check for duplicate admission numbers and emails
                $existingUser = User::where('admission_number', $studentData['admission_number'])
                    ->orWhere(function ($query) use ($studentData) {
                        if (!empty($studentData['email'])) {
                            $query->where('email', $studentData['email']);
                        }
                    })
                    ->first();

                if ($existingUser) {
                    $errors[] = "Student at index {$index}: Admission number or email already exists";
                    continue;
                }

                $student = User::create([
                    'name' => $studentData['name'],
                    'admission_number' => $studentData['admission_number'],
                    'email' => $studentData['email'] ?? null,
                    'password' => Hash::make($studentData['password']),
                    'institution_id' => $user->institution_id,
                    'grade_level' => $studentData['grade_level'],
                    'user_type' => 'student',
                ]);

                $createdStudents[] = $student;
            }

            $response = [
                'success' => true,
                'message' => count($createdStudents) . ' students created successfully',
                'data' => $createdStudents
            ];

            if (!empty($errors)) {
                $response['warnings'] = $errors;
                $response['message'] .= ' with ' . count($errors) . ' errors';
            }

            return response()->json($response, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create students. Please try again.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Import students from Excel file.
     * Note: This functionality requires Laravel Excel package to be installed.
     * Install with: composer require maatwebsite/excel
     */
    public function importFromExcel(Request $request): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Excel import functionality is not available. Please install Laravel Excel package (composer require maatwebsite/excel) or use the multiple students endpoint instead.',
            'alternative' => 'Use POST /api/institution/students/multiple to add multiple students at once'
        ], 501);
    }

    /**
     * Update a specific student.
     */
    public function update(Request $request, User $student): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->isInstitution()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only institution users can update students.'
            ], 403);
        }

        // Check if the student belongs to the authenticated institution
        if ($student->institution_id !== $user->institution_id || $student->user_type !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You can only update your own students.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'admission_number' => 'required|string|max:50|unique:users,admission_number,' . $student->id,
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $student->id,
            'grade_level' => 'required|string|max:50',
        ], [
            'name.required' => 'Student name is required.',
            'name.max' => 'Student name cannot exceed 255 characters.',
            'admission_number.required' => 'Admission number is required.',
            'admission_number.max' => 'Admission number cannot exceed 50 characters.',
            'admission_number.unique' => 'This admission number is already registered.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'grade_level.required' => 'Grade level is required.',
            'grade_level.max' => 'Grade level cannot exceed 50 characters.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $student->update([
                'name' => $request->name,
                'admission_number' => $request->admission_number,
                'email' => $request->email,
                'grade_level' => $request->grade_level,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Student updated successfully',
                'data' => $student
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update student. Please try again.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Delete a specific student.
     */
    public function destroy(User $student): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->isInstitution()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only institution users can delete students.'
            ], 403);
        }

        // Check if the student belongs to the authenticated institution
        if ($student->institution_id !== $user->institution_id || $student->user_type !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You can only delete your own students.'
            ], 403);
        }

        try {
            $student->delete();

            return response()->json([
                'success' => true,
                'message' => 'Student deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete student. Please try again.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get a specific student.
     */
    public function show(User $student): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user->isInstitution()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Only institution users can view students.'
            ], 403);
        }

        // Check if the student belongs to the authenticated institution
        if ($student->institution_id !== $user->institution_id || $student->user_type !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You can only view your own students.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $student
        ]);
    }
}

/**
 * Excel Import Class for Students
 * Note: This class requires Laravel Excel package to be installed.
 * Install with: composer require maatwebsite/excel
 * 
 * Uncomment the following code after installing Laravel Excel:
 * 
 * class StudentImport implements ToModel, WithHeadingRow, WithValidation
 * {
 *     private $institutionId;
 *     private $importedCount = 0;
 *     private $errors = [];
 *     private $warnings = [];
 * 
 *     public function __construct($institutionId)
 *     {
 *         $this->institutionId = $institutionId;
 *     }
 * 
 *     public function model(array $row)
 *     {
 *         // Skip empty rows
 *         if (empty($row['name']) || empty($row['admission_number'])) {
 *             return null;
 *         }
 * 
 *         // Check for duplicates
 *         $existingUser = User::where('admission_number', $row['admission_number'])
 *             ->orWhere(function ($query) use ($row) {
 *                 if (!empty($row['email'])) {
 *                     $query->where('email', $row['email']);
 *                 }
 *             })
 *             ->first();
 * 
 *         if ($existingUser) {
 *             $this->warnings[] = "Row " . ($this->importedCount + 1) . ": Admission number or email already exists";
 *             return null;
 *         }
 * 
 *         $this->importedCount++;
 * 
 *         return new User([
 *             'name' => $row['name'],
 *             'admission_number' => $row['admission_number'],
 *             'email' => $row['email'] ?? null,
 *             'password' => Hash::make($row['password'] ?? 'password123'), // Default password
 *             'institution_id' => $this->institutionId,
 *             'grade_level' => $row['grade_level'],
 *             'user_type' => 'student',
 *         ]);
 *     }
 * 
 *     public function rules(): array
 *     {
 *         return [
 *             'name' => 'required|string|max:255',
 *             'admission_number' => 'required|string|max:50',
 *             'email' => 'nullable|email|max:255',
 *             'grade_level' => 'required|string|max:50',
 *         ];
 *     }
 * 
 *     public function getImportedCount(): int
 *     {
 *         return $this->importedCount;
 *     }
 * 
 *     public function getErrors(): array
 *     {
 *         return $this->errors;
 *     }
 * 
 *     public function getWarnings(): array
 *     {
 *         return $this->warnings;
 *     }
 * }
 */
