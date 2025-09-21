<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Institution;
use App\Models\Wallet;
use App\Models\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Register a new student user
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15|regex:/^254[0-9]{9}$/|unique:users',
            'email' => 'nullable|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'mpesa_phone' => 'required|string|max:15|regex:/^254[0-9]{9}$/',
            'institution_id' => 'nullable|exists:institutions,id',
            'grade_level' => 'nullable|string|max:50',
        ], [
            'name.required' => 'Your full name is required.',
            'name.max' => 'Your name cannot exceed 255 characters.',
            'phone_number.required' => 'Your phone number is required.',
            'phone_number.regex' => 'Phone number must be in the format 254XXXXXXXXX (e.g., 254700000000).',
            'phone_number.unique' => 'This phone number is already registered. Please use a different number or try logging in.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered. Please use a different email or try logging in.',
            'password.required' => 'A password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'mpesa_phone.required' => 'M-Pesa phone number is required for payments.',
            'mpesa_phone.regex' => 'M-Pesa phone number must be in the format 254XXXXXXXXX (e.g., 254700000000).',
            'institution_id.exists' => 'The selected institution does not exist.',
            'grade_level.max' => 'Grade level cannot exceed 50 characters.',
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
                'mpesa_phone' => $request->mpesa_phone,
                'institution_id' => $request->institution_id,
                'grade_level' => $request->grade_level,
                'user_type' => 'student',
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

            // Add mpesa_phone to user data
            $userData = $user->toArray();
            $userData['mpesa_phone'] = $user->mpesa_phone;

            return response()->json([
                'success' => true,
                'message' => 'Student registered successfully',
                'data' => [
                    'user' => $userData,
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
        $validator = Validator::make($request->all(), [
            'institution_name' => 'required|string|max:255',
            'institution_email' => 'nullable|string|email|max:255|unique:institutions,email',
            'institution_phone' => 'required|string|max:20',
            'institution_address' => 'required|string|max:500',
            'mpesa_phone' => 'required|string|max:15|regex:/^254[0-9]{9}$/',
            'admin_name' => 'required|string|max:255',
            'admin_phone_number' => 'required|string|max:15|regex:/^254[0-9]{9}$/|unique:users,phone_number',
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
            'mpesa_phone.required' => 'M-Pesa phone number is required for payments.',
            'mpesa_phone.regex' => 'M-Pesa phone number must be in the format 254XXXXXXXXX (e.g., 254700000000).',
            'admin_name.required' => 'Admin name is required.',
            'admin_name.max' => 'Admin name cannot exceed 255 characters.',
            'admin_phone_number.required' => 'Admin phone number is required.',
            'admin_phone_number.regex' => 'Admin phone number must be in the format 254XXXXXXXXX (e.g., 254700000000).',
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
                'mpesa_phone' => $request->mpesa_phone,
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

            // Add mpesa_phone to user data (from institution)
            $userData = $user->toArray();
            $userData['mpesa_phone'] = $institution->mpesa_phone;

            return response()->json([
                'success' => true,
                'message' => 'Institution registered successfully',
                'data' => [
                    'institution' => $institution,
                    'admin_user' => $userData,
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
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|regex:/^254[0-9]{9}$/',
            'password' => 'required|string',
        ], [
            'phone_number.required' => 'Your phone number is required.',
            'phone_number.regex' => 'Phone number must be in the format 254XXXXXXXXX (e.g., 254700000000).',
            'password.required' => 'Your password is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please check your login information and try again.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('phone_number', $request->phone_number)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No account found with this phone number. Please check your phone number or register for a new account.'
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

        // Get mpesa_phone based on user type
        $mpesaPhone = null;
        if ($user->isInstitution() && $user->institution) {
            // For institution users, get mpesa_phone from institutions table
            $mpesaPhone = $user->institution->mpesa_phone;
        } else {
            // For individual users (students), get mpesa_phone from users table
            $mpesaPhone = $user->mpesa_phone;
        }

        // Add mpesa_phone to user data
        $userData = $user->toArray();
        $userData['mpesa_phone'] = $mpesaPhone;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $userData,
                'access_token' => $token,
                'token_type' => 'Bearer',
                'dashboard' => $dashboardData
            ]
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

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * Send password reset code
     */
    public function forgotPassword(Request $request)
    {
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

            // TODO: Send email with reset code
            // For now, we'll just return the code in development
            // In production, this should be sent via email/SMS
            $emailSent = $this->sendResetCodeEmail($user->email, $resetCode->code);

            return response()->json([
                'success' => true,
                'message' => 'Password reset code sent successfully',
                'data' => [
                    'phone_number' => $request->phone_number,
                    'email_sent' => $emailSent,
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
     * Send reset code via email (placeholder for now)
     */
    private function sendResetCodeEmail(string $email, string $code): bool
    {
        // TODO: Implement actual email sending
        // For now, just log the code (remove in production)
        \Log::info("Password reset code for {$email}: {$code}");
        
        // In production, you would use Laravel Mail here
        // Mail::to($email)->send(new PasswordResetCodeMail($code));
        
        return true; // Assume email was sent successfully
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

        return [
            'token_balance' => $user->wallet->balance ?? 0,
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
        if ($user->isStudent()) {
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
}