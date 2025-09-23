<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\TokenTransaction;
use App\Models\TokenUsage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Get user's transaction history with summary
     */
    public function myTransactions(Request $request)
    {
        $user = $request->user();

        // Get summary data
        $summary = $this->getUserTransactionSummary($user);

        // Get transactions
        $transactions = $this->getUserTransactions($user, $request);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'transactions' => $transactions
            ]
        ]);
    }

    /**
     * Get institution's transaction history with summary
     */
    public function institutionTransactions(Request $request)
    {
        $user = $request->user();

        if (!$user->isInstitution()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Get all users associated with this institution
        // This includes: institution admin, students, and parents of students at this institution
        $institutionUserIds = collect();
        
        // 1. Add the institution admin (the user making this request)
        $institutionUserIds->push($user->id);
        
        // 2. Add all students from this institution
        $institutionStudents = User::where('institution_id', $user->institution_id)
            ->where('user_type', 'student')
            ->pluck('id');
        $institutionUserIds = $institutionUserIds->merge($institutionStudents);
        
        // 3. Add parents who have learners at this institution
        // (Note: This would require a relationship between ParentLearner and institution students)
        // For now, we'll focus on institution admin and students

        // Get summary data for institution
        $summary = $this->getInstitutionTransactionSummary($institutionUserIds);

        // Get transactions for institution
        $transactions = $this->getInstitutionTransactions($institutionUserIds, $request);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $summary,
                'transactions' => $transactions
            ]
        ]);
    }

    /**
     * Get user transaction summary
     */
    private function getUserTransactionSummary($user)
    {
        $currentMonth = now()->startOfMonth();
        
        // Total spent (successful payments)
        $totalSpent = Payment::where('user_id', $user->id)
            ->where('status', 'successful')
            ->sum('amount');

        // Total purchases (successful payments count)
        $totalPurchases = Payment::where('user_id', $user->id)
            ->where('status', 'successful')
            ->count();

        // This month purchase amount
        $thisMonthSpent = Payment::where('user_id', $user->id)
            ->where('status', 'successful')
            ->where('created_at', '>=', $currentMonth)
            ->sum('amount');

        // Total tokens credited
        $totalTokensCredited = TokenTransaction::whereHas('wallet', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('transaction_type', 'credit')
        ->sum('tokens');

        // Total tokens used
        $totalTokensUsed = TokenUsage::whereHas('attempt.student', function ($q) use ($user) {
            $q->where('id', $user->id);
        })->sum('tokens_used');

        return [
            'total_spent' => number_format($totalSpent, 2),
            'total_purchases' => $totalPurchases,
            'this_month_spent' => number_format($thisMonthSpent, 2),
            'total_tokens_credited' => $totalTokensCredited,
            'total_tokens_used' => $totalTokensUsed,
            'current_balance' => $user->getEffectiveWallet()->balance ?? 0
        ];
    }

    /**
     * Get institution transaction summary
     */
    private function getInstitutionTransactionSummary($userIds)
    {
        $currentMonth = now()->startOfMonth();
        
        // Total spent by all users (institution admin + students)
        $totalSpent = Payment::whereIn('user_id', $userIds)
            ->where('status', 'successful')
            ->sum('amount');

        // Total purchases by all users
        $totalPurchases = Payment::whereIn('user_id', $userIds)
            ->where('status', 'successful')
            ->count();

        // This month purchase amount
        $thisMonthSpent = Payment::whereIn('user_id', $userIds)
            ->where('status', 'successful')
            ->where('created_at', '>=', $currentMonth)
            ->sum('amount');

        // Total tokens credited to all users
        $totalTokensCredited = TokenTransaction::whereHas('wallet', function ($q) use ($userIds) {
            $q->whereIn('user_id', $userIds);
        })
        ->where('transaction_type', 'credit')
        ->sum('tokens');

        // Total tokens used by all students (only students make attempts)
        $studentIds = $userIds->filter(function ($userId) {
            return User::find($userId)->isStudent();
        });
        $totalTokensUsed = TokenUsage::whereHas('attempt.student', function ($q) use ($studentIds) {
            $q->whereIn('id', $studentIds);
        })->sum('tokens_used');

        return [
            'total_spent' => number_format($totalSpent, 2),
            'total_purchases' => $totalPurchases,
            'this_month_spent' => number_format($thisMonthSpent, 2),
            'total_tokens_credited' => $totalTokensCredited,
            'total_tokens_used' => $totalTokensUsed,
            'total_students' => count($studentIds)
        ];
    }

    /**
     * Get user transactions
     */
    private function getUserTransactions($user, $request)
    {
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        // Get payments (credits)
        $payments = Payment::where('user_id', $user->id)
            ->where('status', 'successful')
            ->with(['mpesaPayment', 'bankPayment'])
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => 'payment_' . $payment->id,
                    'type' => 'credit',
                    'transaction_type' => 'Token Purchase',
                    'date' => $payment->created_at->format('Y-m-d H:i:s'),
                    'amount' => number_format($payment->amount, 2),
                    'tokens' => $payment->tokens,
                    'status' => $payment->status,
                    'channel' => $payment->channel,
                    'reference' => $payment->mpesaPayment->transaction_id ?? $payment->bankPayment->transaction_id ?? null,
                    'description' => "Token purchase via {$payment->channel}",
                    'currency' => $payment->currency
                ];
            });

        // Get token usages (debits) - group by attempt to avoid duplicates
        $tokenUsages = TokenUsage::whereHas('attempt.student', function ($q) use ($user) {
            $q->where('id', $user->id);
        })
        ->with(['attempt.assessment'])
        ->get()
        ->groupBy('attempt_id')
        ->map(function ($usages) {
            $attempt = $usages->first()->attempt;
            $totalTokensUsed = $usages->sum('tokens_used');
            
            return [
                'id' => 'attempt_' . $attempt->id,
                'type' => 'debit',
                'transaction_type' => 'Assessment Attempt',
                'date' => $attempt->started_at->format('Y-m-d H:i:s'),
                'amount' => null,
                'tokens' => -$totalTokensUsed,
                'status' => $attempt->isCompleted() ? 'completed' : 'in_progress',
                'assessment_title' => $attempt->assessment->title,
                'assessment_grade' => $attempt->assessment->grade_level,
                'assessment_subject' => $attempt->assessment->subject->name ?? 'N/A',
                'description' => "Assessment attempt: {$attempt->assessment->title}",
                'attempt_id' => $attempt->id
            ];
        });

        // Combine and sort by date
        $allTransactions = $payments->concat($tokenUsages)
            ->sortByDesc('date')
            ->values();

        // Manual pagination
        $total = $allTransactions->count();
        $offset = ($page - 1) * $perPage;
        $items = $allTransactions->slice($offset, $perPage)->values();

        return [
            'current_page' => $page,
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Get institution transactions
     */
    private function getInstitutionTransactions($userIds, $request)
    {
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        // Get payments from all users (institution admin + students)
        $payments = Payment::whereIn('user_id', $userIds)
            ->where('status', 'successful')
            ->with(['user', 'mpesaPayment', 'bankPayment'])
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => 'payment_' . $payment->id,
                    'type' => 'credit',
                    'transaction_type' => 'Token Purchase',
                    'date' => $payment->created_at->format('Y-m-d H:i:s'),
                    'amount' => number_format($payment->amount, 2),
                    'tokens' => $payment->tokens,
                    'status' => $payment->status,
                    'channel' => $payment->channel,
                    'reference' => $payment->mpesaPayment->transaction_id ?? $payment->bankPayment->transaction_id ?? null,
                    'description' => "Token purchase via {$payment->channel}",
                    'currency' => $payment->currency,
                    'student_name' => $payment->user->name,
                    'student_id' => $payment->user->id
                ];
            });

        // Get token usages from all students - group by attempt to avoid duplicates
        $studentIds = $userIds->filter(function ($userId) {
            return User::find($userId)->isStudent();
        });
        $tokenUsages = TokenUsage::whereHas('attempt.student', function ($q) use ($studentIds) {
            $q->whereIn('id', $studentIds);
        })
        ->with(['attempt.assessment', 'attempt.student'])
        ->get()
        ->groupBy('attempt_id')
        ->map(function ($usages) {
            $attempt = $usages->first()->attempt;
            $totalTokensUsed = $usages->sum('tokens_used');
            
            return [
                'id' => 'attempt_' . $attempt->id,
                'type' => 'debit',
                'transaction_type' => 'Assessment Attempt',
                'date' => $attempt->started_at->format('Y-m-d H:i:s'),
                'amount' => null,
                'tokens' => -$totalTokensUsed,
                'status' => $attempt->isCompleted() ? 'completed' : 'in_progress',
                'assessment_title' => $attempt->assessment->title,
                'assessment_grade' => $attempt->assessment->grade_level,
                'assessment_subject' => $attempt->assessment->subject->name ?? 'N/A',
                'description' => "Assessment attempt: {$attempt->assessment->title}",
                'attempt_id' => $attempt->id,
                'student_name' => $attempt->student->name,
                'student_id' => $attempt->student->id
            ];
        });

        // Combine and sort by date
        $allTransactions = $payments->concat($tokenUsages)
            ->sortByDesc('date')
            ->values();

        // Manual pagination
        $total = $allTransactions->count();
        $offset = ($page - 1) * $perPage;
        $items = $allTransactions->slice($offset, $perPage)->values();

        return [
            'current_page' => $page,
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'last_page' => ceil($total / $perPage)
        ];
    }
}
