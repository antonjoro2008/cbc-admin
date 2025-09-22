<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\TokenTransaction;
use App\Models\TokenUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard overview data
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $effectiveWallet = $user->getEffectiveWallet();
        
        $data = [
            'user' => $user->load('institution', 'wallet'),
            'user_type' => $user->user_type, // Explicitly include user type for frontend
            'token_balance' => $effectiveWallet->balance ?? 0,
            'minutes_balance' => $effectiveWallet->available_minutes ?? 0,
            'assessment_stats' => $this->getAssessmentStats($user),
            'recent_assessments' => $this->getRecentAssessments($user),
            'recent_attempts' => $this->getRecentAttempts($user),
        ];

        // Include institution details at top level for institution admin users
        if ($user->user_type === 'institution' && $user->institution) {
            $data['institution'] = $user->institution;
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get token balance
     */
    public function tokenBalance(Request $request)
    {
        $user = $request->user();
        
        $effectiveWallet = $user->getEffectiveWallet();
        
        return response()->json([
            'success' => true,
            'data' => [
                'user_type' => $user->user_type, // Include user type for frontend
                'token_balance' => $effectiveWallet->balance ?? 0,
                'minutes_balance' => $effectiveWallet->available_minutes ?? 0,
                'wallet_id' => $effectiveWallet->id ?? null,
            ]
        ]);
    }

    /**
     * Get assessment statistics
     */
    public function assessmentStats(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => $this->getAssessmentStats($user)
        ]);
    }

    /**
     * Get recent assessments
     */
    public function recentAssessments(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => $this->getRecentAssessments($user)
        ]);
    }

    /**
     * Get token history
     */
    public function tokenHistory(Request $request)
    {
        $user = $request->user();
        
        $effectiveWallet = $user->getEffectiveWallet();
        $tokenHistory = TokenTransaction::where('wallet_id', $effectiveWallet->id ?? 0)
            ->with(['wallet.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $tokenHistory
        ]);
    }

    /**
     * Get assessment statistics for a user
     */
    private function getAssessmentStats($user)
    {
        $attempts = AssessmentAttempt::where('student_id', $user->id);
        
        $totalAttempts = $attempts->count();
        $completedAttempts = $attempts->whereNotNull('completed_at')->count();
        $inProgressAttempts = $attempts->whereNull('completed_at')->count();
        
        // Calculate average score based on marked questions only
        $averageScore = $this->calculateAverageScore($user->id);

        $totalTokensUsed = TokenUsage::whereHas('attempt', function($query) use ($user) {
            $query->where('student_id', $user->id);
        })->sum('tokens_used');

        return [
            'total_attempts' => $totalAttempts,
            'completed_attempts' => $completedAttempts,
            'in_progress_attempts' => $inProgressAttempts,
            'average_score' => round($averageScore, 2),
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
            // For students, get assessments they can access
            $assessments = Assessment::whereHas('attempts', function($query) use ($user) {
                $query->where('student_id', $user->id);
            })
            ->orWhere('created_by', $user->id)
            ->with(['subject', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        } elseif ($user->isInstitution()) {
            // For institutions, get assessments from their institution
            $assessments = Assessment::whereHas('creator', function($query) use ($user) {
                $query->where('institution_id', $user->institution_id);
            })
            ->with(['subject', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        } else {
            // For admins, get all recent assessments
            $assessments = Assessment::with(['subject', 'creator'])
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
            $attempts = AssessmentAttempt::where('student_id', $user->id)
                ->with(['assessment.subject'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } elseif ($user->isInstitution()) {
            $attempts = AssessmentAttempt::whereHas('student', function($query) use ($user) {
                $query->where('institution_id', $user->institution_id);
            })
            ->with(['assessment.subject', 'student'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        } else {
            $attempts = AssessmentAttempt::with(['assessment.subject', 'student'])
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
