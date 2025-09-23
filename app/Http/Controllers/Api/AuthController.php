<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Institution;
use App\Models\Wallet;
use App\Models\PasswordResetCode;
use App\Services\SmsNotificationService;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register a new user (student or parent)
     */
    public function register(Request $request)
    {
        // Standardize phone number before validation
        $request->merge(['mpesa_phone' => $this->standardizePhoneNumber($request->phone_number)]);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => [
                'required',
                'string',
                'max:15',
                'unique:users,mpesa_phone',
                function ($attribute, $value, $fail) {
                    if (!$this->isValidMpesaNumber($value)) {
                        $fail('Phone number must be a valid M-Pesa number with supported prefix.');
                    }
                }
            ],
            'email' => 'nullable|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'grade_level' => 'nullable|string|max:50',
            'user_type' => 'required|string|in:student,parent',
        ], [
            'name.required' => 'Your full name is required.',
            'name.max' => 'Your name cannot exceed 255 characters.',
            'phone_number.required' => 'Your phone number is required.',
            'phone_number.unique' => 'This phone number is already registered. Please use a different number or try logging in.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered. Please use a different email or try logging in.',
            'password.required' => 'A password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'grade_level.max' => 'Grade level cannot exceed 50 characters.',
            'user_type.required' => 'User type is required.',
            'user_type.in' => 'User type must be either student or parent.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please check your information and try again.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'institution_id' => null,
                'grade_level' => $request->grade_level,
                'user_type' => $request->user_type,
            ]);

            // Create wallet for the user
            // TODO: Remove this temporary feature - crediting new accounts with 20 tokens
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 20, // Temporary: credit new individual accounts with 20 tokens
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            // Get dashboard data
            $dashboardData = $this->getDashboardData($user);

            // Load user with relationships
            $user->load('institution');

            // Send registration SMS
            SmsNotificationService::sendRegistrationSms($user);

            // Prepare user data
            $userData = $user->toArray();

            $userTypeLabel = ucfirst($request->user_type);

            return response()->json([
                'success' => true,
                'message' => "$userTypeLabel registered successfully",
                'data' => [
                    'user' => $userData,
                    'user_type' => $user->user_type, // Explicitly include user type for frontend
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'dashboard' => $dashboardData
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again or contact support if the problem persists.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Register a new institution
     */
    public function registerInstitution(Request $request)
    {
        // Standardize phone numbers before validation
        $request->merge([
            'admin_phone_number' => $this->standardizePhoneNumber($request->admin_phone_number)
        ]);
        
        $validator = Validator::make($request->all(), [
            'institution_name' => 'required|string|max:255',
            'institution_email' => 'nullable|string|email|max:255|unique:institutions,email',
            'institution_phone' => 'required|string|max:20',
            'institution_address' => 'required|string|max:500',
            'admin_name' => 'required|string|max:255',
            'admin_phone_number' => [
                'required',
                'string',
                'max:15',
                'unique:users,phone_number',
                function ($attribute, $value, $fail) {
                    if (!$this->isValidMpesaNumber($value)) {
                        $fail('Phone number must be a valid M-Pesa number with supported prefix.');
                    }
                }
            ],
            'admin_email' => 'nullable|string|email|max:255|unique:users,email',
            'admin_password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'institution_name.required' => 'Institution name is required.',
            'institution_name.max' => 'Institution name cannot exceed 255 characters.',
            'institution_email.email' => 'Please provide a valid institution email address.',
            'institution_email.unique' => 'This institution email is already registered.',
            'institution_phone.required' => 'Institution phone number is required.',
            'institution_phone.max' => 'Institution phone number cannot exceed 20 characters.',
            'institution_address.required' => 'Institution address is required.',
            'institution_address.max' => 'Institution address cannot exceed 500 characters.',
            'admin_name.required' => 'Admin name is required.',
            'admin_name.max' => 'Admin name cannot exceed 255 characters.',
            'admin_phone_number.required' => 'Admin phone number is required.',
            'admin_phone_number.unique' => 'This admin phone number is already registered. Please use a different number.',
            'admin_email.email' => 'Please provide a valid admin email address.',
            'admin_email.unique' => 'This admin email is already registered. Please use a different email.',
            'admin_password.required' => 'Admin password is required.',
            'admin_password.confirmed' => 'Admin password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please check your institution information and try again.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Create institution
            $institution = Institution::create([
                'name' => $request->institution_name,
                'email' => $request->institution_email,
                'phone' => $request->institution_phone,
                'address' => $request->institution_address,
            ]);

            // Create institution admin user
            $user = User::create([
                'name' => $request->admin_name,
                'phone_number' => $request->admin_phone_number,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'institution_id' => $institution->id,
                'user_type' => 'institution',
            ]);

            // Create wallet for the institution
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            // Get dashboard data
            $dashboardData = $this->getDashboardData($user);

            // Load user with relationships
            $user->load('institution');

            // Send registration SMS
            SmsNotificationService::sendRegistrationSms($user);

            // Prepare user data
            $userData = $user->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Institution registered successfully',
                'data' => [
                    'institution' => $institution,
                    'user' => $userData,
                    'user_type' => $user->user_type, // Explicitly include user type for frontend
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'dashboard' => $dashboardData
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Institution registration failed. Please try again or contact support if the problem persists.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        // Standardize login_identifier if it looks like a phone number
        $loginIdentifier = $request->login_identifier;
        if (preg_match('/^[0-9+\-\s()]+$/', $loginIdentifier)) {
            // It looks like a phone number, standardize it
            $loginIdentifier = $this->standardizePhoneNumber($loginIdentifier);
            $request->merge(['login_identifier' => $loginIdentifier]);
        }
        
        $validator = Validator::make($request->all(), [
            'login_identifier' => 'required|string',
            'password' => 'required|string',
        ], [
            'login_identifier.required' => 'Phone number or admission number is required.',
            'password.required' => 'Your password is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please check your login information and try again.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Try to find user by phone_number first, then by admission_number
        $user = User::where('phone_number', $request->login_identifier)
            ->orWhere('admission_number', $request->login_identifier)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this identifier. Please check your phone number/admission number or register for a new account.'
            ], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Incorrect password. Please check your password and try again.'
            ], 401);
        }

        // Revoke existing tokens
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        // Get dashboard data
        $dashboardData = $this->getDashboardData($user);

        // Load user with relationships
        $user->load('institution', 'wallet');

        // Prepare user data
        $userData = $user->toArray();

        $responseData = [
            'user' => $userData,
            'user_type' => $user->user_type, // Explicitly include user type for frontend
            'access_token' => $token,
            'token_type' => 'Bearer',
            'dashboard' => $dashboardData
        ];

        // Include institution details at top level for institution admin users
        if ($user->user_type === 'institution' && $user->institution) {
            $responseData['institution'] = $user->institution;
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $responseData
        ]);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        // Revoke current token
        $user->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        $responseData = [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user_type' => $user->user_type, // Include user type for frontend
        ];

        // Include institution details at top level for institution admin users
        if ($user->user_type === 'institution' && $user->institution) {
            $responseData['institution'] = $user->institution;
        }

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => $responseData
        ]);
    }

    /**
     * Send password reset code
     */
    public function forgotPassword(Request $request)
    {
        // Standardize phone number before validation
        $request->merge(['phone_number' => $this->standardizePhoneNumber($request->phone_number)]);
        
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^254[0-9]{9}$/',
        ], [
            'phone_number.required' => 'Your phone number is required.',
            'phone_number.regex' => 'Phone number must be in the format 254XXXXXXXXX (e.g., 254700000000).',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid phone number.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this phone number. Please check your phone number or register for a new account.'
            ], 404);
        }

        try {
            // Create reset code
            $resetCode = PasswordResetCode::createForPhone($request->phone_number, $user->email);

            // Send SMS with reset code
            SmsNotificationService::sendPasswordResetSms($user, $resetCode->code);

            // Send email with reset code (if user has email)
            if ($user->email) {
                EmailNotificationService::sendPasswordResetEmail($user, $resetCode->code);
            }

            return response()->json([
                'success' => true,
                'message' => 'Password reset code sent successfully',
                'data' => [
                    'phone_number' => $request->phone_number,
                    'sms_sent' => true,
                    'email_sent' => !empty($user->email),
                    // Remove this in production - only for development
                    'reset_code' => app()->environment('local') ? $resetCode->code : null,
                    'expires_in_minutes' => 15
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reset code. Please try again or contact support if the problem persists.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Verify password reset code
     */
    public function verifyResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^254[0-9]{9}$/',
            'code' => 'required|string|size:6',
        ], [
            'phone_number.required' => 'Your phone number is required.',
            'phone_number.regex' => 'Phone number must be in the format 254XXXXXXXXX (e.g., 254700000000).',
            'code.required' => 'Reset code is required.',
            'code.size' => 'Reset code must be exactly 6 digits.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide valid information.',
                'errors' => $validator->errors()
            ], 422);
        }

        $resetCode = PasswordResetCode::findValidCode($request->phone_number, $request->code);

        if (!$resetCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset code. Please request a new code or check your phone number.'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reset code verified successfully',
            'data' => [
                'phone_number' => $request->phone_number,
                'code_verified' => true,
                'expires_at' => $resetCode->expires_at
            ]
        ]);
    }

    /**
     * Reset password using verified code
     */
    public function resetPassword(Request $request)
    {
        // Standardize phone number before validation
        $request->merge(['phone_number' => $this->standardizePhoneNumber($request->phone_number)]);
        
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^254[0-9]{9}$/',
            'code' => 'required|string|size:6',
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'phone_number.required' => 'Your phone number is required.',
            'phone_number.regex' => 'Phone number must be in the format 254XXXXXXXXX (e.g., 254700000000).',
            'code.required' => 'Reset code is required.',
            'code.size' => 'Reset code must be exactly 6 digits.',
            'password.required' => 'New password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please check your information and try again.',
                'errors' => $validator->errors()
            ], 422);
        }

        $resetCode = PasswordResetCode::findValidCode($request->phone_number, $request->code);

        if (!$resetCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired reset code. Please request a new code or check your phone number.'
            ], 400);
        }

        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found. Please contact support.'
            ], 404);
        }

        try {
            // Update user password
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Mark reset code as used
            $resetCode->markAsUsed();

            // Revoke all existing tokens for security
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully. Please login with your new password.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password. Please try again or contact support if the problem persists.',
                'error' => app()->environment('local') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }


    /**
     * Get dashboard data for a user
     */
    private function getDashboardData($user)
    {
        // Get assessment statistics
        $assessmentStats = $this->getAssessmentStats($user);

        // Get recent assessments
        $recentAssessments = $this->getRecentAssessments($user);

        // Get recent attempts
        $recentAttempts = $this->getRecentAttempts($user);

        $effectiveWallet = $user->getEffectiveWallet();

        return [
            'token_balance' => $effectiveWallet->balance ?? 0,
            'assessment_stats' => $assessmentStats,
            'recent_assessments' => $recentAssessments,
            'recent_attempts' => $recentAttempts,
        ];
    }

    /**
     * Get assessment statistics for a user
     */
    private function getAssessmentStats($user)
    {
        $attempts = \App\Models\AssessmentAttempt::where('student_id', $user->id);

        $totalAttempts = $attempts->count();
        $completedAttempts = $attempts->whereNotNull('completed_at')->count();
        $inProgressAttempts = $attempts->whereNull('completed_at')->count();

        // Calculate average score based on marked questions only
        $averageScore = $this->calculateAverageScore($user->id);

        $totalTokensUsed = \App\Models\TokenUsage::whereHas('attempt', function ($query) use ($user) {
            $query->where('student_id', $user->id);
        })->sum('tokens_used');

        return [
            'total_attempts' => $totalAttempts,
            'completed_attempts' => $completedAttempts,
            'in_progress_attempts' => $inProgressAttempts,
            'average_score' => round($averageScore),
            'total_tokens_used' => $totalTokensUsed,
            'completion_rate' => $totalAttempts > 0 ? round(($completedAttempts / $totalAttempts) * 100, 2) : 0,
        ];
    }

    /**
     * Get recent assessments for a user
     */
    private function getRecentAssessments($user)
    {
        if ($user->isStudent() || $user->isParent()) {
            $assessments = \App\Models\Assessment::whereHas('attempts', function ($query) use ($user) {
                $query->where('student_id', $user->id);
            })
                ->orWhere('created_by', $user->id)
                ->with(['subject', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } elseif ($user->isInstitution()) {
            $assessments = \App\Models\Assessment::whereHas('creator', function ($query) use ($user) {
                $query->where('institution_id', $user->institution_id);
            })
                ->with(['subject', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } else {
            $assessments = \App\Models\Assessment::with(['subject', 'creator'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return $assessments;
    }

    /**
     * Get recent attempts for a user
     */
    private function getRecentAttempts($user)
    {
        if ($user->isStudent()) {
            $attempts = \App\Models\AssessmentAttempt::where('student_id', $user->id)
                ->with(['assessment.subject'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } elseif ($user->isInstitution()) {
            $attempts = \App\Models\AssessmentAttempt::whereHas('student', function ($query) use ($user) {
                $query->where('institution_id', $user->institution_id);
            })
                ->with(['assessment.subject', 'student'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } else {
            $attempts = \App\Models\AssessmentAttempt::with(['assessment.subject', 'student'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        return $attempts;
    }

    /**
     * Calculate average score based on marked questions only
     */
    private function calculateAverageScore($userId)
    {
        // Get all attempt answers that have feedback (meaning they were marked)
        $markedAttemptAnswers = \App\Models\AttemptAnswer::whereHas('attempt', function ($query) use ($userId) {
            $query->where('student_id', $userId);
        })
            ->whereHas('feedback')
            ->with(['question', 'feedback']);

        $totalMarksAwarded = $markedAttemptAnswers->sum('marks_awarded');

        // Get total possible marks for questions that were marked
        $totalPossibleMarks = $markedAttemptAnswers->get()->sum(function ($attemptAnswer) {
            return $attemptAnswer->question->marks;
        });

        if ($totalPossibleMarks > 0) {
            return round(($totalMarksAwarded / $totalPossibleMarks) * 100, 2);
        }

        return 0;
    }

    /**
     * Standardize phone number to 254... format
     */
    protected function standardizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Handle different formats
        if (str_starts_with($phoneNumber, '254')) {
            // Already in 254 format
            return $phoneNumber;
        } elseif (str_starts_with($phoneNumber, '0')) {
            // Convert from 07... format to 2547...
            return '254' . substr($phoneNumber, 1);
        } elseif (str_starts_with($phoneNumber, '7')) {
            // Convert from 7... format to 2547...
            return '254' . $phoneNumber;
        }
        
        // If none of the above, assume it's already in the correct format
        return $phoneNumber;
    }

    /**
     * Check if a phone number is a valid M-Pesa number
     */
    private function isValidMpesaNumber(string $phoneNumber): bool
    {
        // Valid M-Pesa prefixes
        $validPrefixes = [
            '254700',
            '254701',
            '254702',
            '254703',
            '254704',
            '254705',
            '254706',
            '254707',
            '254708',
            '254709',
            '254710',
            '254711',
            '254712',
            '254713',
            '254714',
            '254715',
            '254716',
            '254717',
            '254718',
            '254719',
            '254720',
            '254721',
            '254722',
            '254723',
            '254724',
            '254725',
            '254726',
            '254727',
            '254728',
            '254729',
            '254740',
            '254741',
            '254742',
            '254743',
            '254745',
            '254746',
            '254748',
            '254757',
            '254758',
            '254759',
            '254768',
            '254769',
            '254790',
            '254791',
            '254792',
            '254793',
            '254794',
            '254795',
            '254796',
            '254797',
            '254798',
            '254799',
            '254110',
            '254111',
            '254112',
            '254113',
            '254114',
            '254115'
        ];

        // Check if phone number starts with any valid prefix
        foreach ($validPrefixes as $prefix) {
            if (str_starts_with($phoneNumber, $prefix)) {
                // Check if the total length is 12 (254 + 9 digits)
                return strlen($phoneNumber) === 12;
            }
        }

        return false;
    }
}