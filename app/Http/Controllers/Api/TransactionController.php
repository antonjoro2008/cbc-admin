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

        // Get all students from this institution
        $institutionStudents = User::where('institution_id', $user->institution_id)
            ->where('user_type', 'student')
            ->pluck('id');

        // Get summary data for institution
        $summary = $this->getInstitutionTransactionSummary($institutionStudents);

        // Get transactions for institution
        $transactions = $this->getInstitutionTransactions($institutionStudents, $request);

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
    private function getInstitutionTransactionSummary($studentIds)
    {
        $currentMonth = now()->startOfMonth();
        
        // Total spent by all students
        $totalSpent = Payment::whereIn('user_id', $studentIds)
            ->where('status', 'successful')
            ->sum('amount');

        // Total purchases by all students
        $totalPurchases = Payment::whereIn('user_id', $studentIds)
            ->where('status', 'successful')
            ->count();

        // This month purchase amount
        $thisMonthSpent = Payment::whereIn('user_id', $studentIds)
            ->where('status', 'successful')
            ->where('created_at', '>=', $currentMonth)
            ->sum('amount');

        // Total tokens credited to all students
        $totalTokensCredited = TokenTransaction::whereHas('wallet', function ($q) use ($studentIds) {
            $q->whereIn('user_id', $studentIds);
        })
        ->where('transaction_type', 'credit')
        ->sum('tokens');

        // Total tokens used by all students
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

        // Get token usages (debits)
        $tokenUsages = TokenUsage::whereHas('attempt.student', function ($q) use ($user) {
            $q->where('id', $user->id);
        })
        ->with(['attempt.assessment'])
        ->get()
        ->map(function ($usage) {
            return [
                'id' => 'usage_' . $usage->id,
                'type' => 'debit',
                'transaction_type' => 'Assessment Attempt',
                'date' => $usage->attempt->started_at->format('Y-m-d H:i:s'),
                'amount' => null,
                'tokens' => -$usage->tokens_used,
                'status' => $usage->attempt->isCompleted() ? 'completed' : 'in_progress',
                'assessment_title' => $usage->attempt->assessment->title,
                'assessment_grade' => $usage->attempt->assessment->grade_level,
                'assessment_subject' => $usage->attempt->assessment->subject->name ?? 'N/A',
                'score' => $usage->attempt->score,
                'description' => "Assessment attempt: {$usage->attempt->assessment->title}",
                'attempt_id' => $usage->attempt->id
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
    private function getInstitutionTransactions($studentIds, $request)
    {
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        // Get payments from all students
        $payments = Payment::whereIn('user_id', $studentIds)
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

        // Get token usages from all students
        $tokenUsages = TokenUsage::whereHas('attempt.student', function ($q) use ($studentIds) {
            $q->whereIn('id', $studentIds);
        })
        ->with(['attempt.assessment', 'attempt.student'])
        ->get()
        ->map(function ($usage) {
            return [
                'id' => 'usage_' . $usage->id,
                'type' => 'debit',
                'transaction_type' => 'Assessment Attempt',
                'date' => $usage->attempt->started_at->format('Y-m-d H:i:s'),
                'amount' => null,
                'tokens' => -$usage->tokens_used,
                'status' => $usage->attempt->isCompleted() ? 'completed' : 'in_progress',
                'assessment_title' => $usage->attempt->assessment->title,
                'assessment_grade' => $usage->attempt->assessment->grade_level,
                'assessment_subject' => $usage->attempt->assessment->subject->name ?? 'N/A',
                'score' => $usage->attempt->score,
                'description' => "Assessment attempt: {$usage->attempt->assessment->title}",
                'attempt_id' => $usage->attempt->id,
                'student_name' => $usage->attempt->student->name,
                'student_id' => $usage->attempt->student->id
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
