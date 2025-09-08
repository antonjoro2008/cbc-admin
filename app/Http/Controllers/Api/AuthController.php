<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Institution;
use App\Models\Wallet;
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
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'mpesa_phone' => 'required|string|max:15|regex:/^254[0-9]{9}$/',
            'institution_id' => 'nullable|exists:institutions,id',
            'grade_level' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'mpesa_phone' => $request->mpesa_phone,
                'institution_id' => $request->institution_id,
                'grade_level' => $request->grade_level,
                'user_type' => 'student',
            ]);

            // Create wallet for the user
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
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
                'message' => 'Registration failed',
                'error' => $e->getMessage()
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
            'institution_email' => 'required|string|email|max:255|unique:institutions,email',
            'institution_phone' => 'required|string|max:20',
            'institution_address' => 'required|string|max:500',
            'mpesa_phone' => 'required|string|max:15|regex:/^254[0-9]{9}$/',
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|string|email|max:255|unique:users,email',
            'admin_password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
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
                'message' => 'Institution registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
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