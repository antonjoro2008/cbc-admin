<?php

namespace App\Http\Controllers\Api;

use App\Models\Wallet;
use App\Models\Payment;
use Illuminate\Http\Request;
use App\Models\TokenTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Get list of payments
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Payment::with(['user.institution']);

        // Filter by user type
        if ($user->isStudent() || $user->isInstitution()) {
            $query->where('user_id', $user->id);
        }
        // Admins can see all payments

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('channel')) {
            $query->where('channel', $request->channel);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $payments = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $payments
        ]);
    }

    /**
     * Create a new payment
     */
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'channel' => 'required|in:mpesa,bank',
            'currency' => 'required|in:KES,USD',
            'tokens' => 'required|integer|min:1',
            'phone_number' => 'required|string|max:15|regex:/^254[0-9]{9}$/',
            'user_id' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $reference = $this->generateReference();
            // Create payment record
            $payment = Payment::create([
                'user_id' => $request->user_id,
                'amount' => $request->amount,
                'channel' => $request->channel,
                'currency' => $request->currency,
                'tokens' => $request->tokens,
                'status' => 'pending',
                'reference' => $reference,
            ]);

            // Create specific payment details based on channel
            if ($request->channel === 'mpesa') {

                $stkPushUrl = "/deposit/stk";
                $msisdn = $request->phone_number;

                $stkPayload = [
                    "orderId" => $reference,
                    "msisdn" => $msisdn,
                    "amount" => $request->amount,
                    "profileId" => 0,
                ];

                $response = $this->callMDarasaAPIPostWithoutToken($stkPayload, $stkPushUrl);
                Log::info("Response from Main Server " . json_encode($response));
                $success = false;
                $message = "Sorry, we encountered an error and deposit failed. Please try later";
                if (!is_null($response)) {

                    Log::info("We got some response");
                    if ($response->Success) {

                        $success = true;
                        $message = "Please authorize the deposit request to your MPESA phone.";
                    }
                }
            } elseif ($request->channel === 'bank') {
                $this->createBankPayment($payment, $request);
            }

            DB::commit();

            return response()->json([
                'success' => $success,
                'message' => $message,
                'data' => $payment->load('user.institution')
            ], 201);

        } catch (\Exception $e) {
            Log::error("ERROR:: " . $e->getMessage());
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Payment creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment details
     */
    public function show(Request $request, Payment $payment)
    {
        $user = $request->user();

        // Check if user has access to this payment
        if ($user->isStudent() || $user->isInstitution()) {
            if ($payment->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this payment'
                ], 403);
            }
        }
        // Admins can access all payments

        $payment->load(['user.institution', 'mpesaPayment', 'bankPayment']);

        return response()->json([
            'success' => true,
            'data' => $payment
        ]);
    }

    /**
     * Update payment status (for webhook or admin use)
     */
    public function updateStatus(Request $request)
    {
        $payment = Payment::where('reference', $request->reference)->first();

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,successful,failed,cancelled',
            'reference' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $oldStatus = $payment->status;
            $payment->update([
                'status' => $request->status,
                'reference' => $request->reference ?? $payment->reference,
            ]);

            // If payment is successful, add tokens to wallet
            if ($request->status === 'successful' && $oldStatus !== 'successful') {

                $this->createMpesaPayment($payment, $request);
                $this->processSuccessfulPayment($payment);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment received successfully. Tokens have been credited to your account',
                'data' => $payment->fresh()->load('user.institution')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Payment status update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create M-Pesa payment details
     */
    private function createMpesaPayment($payment, $request)
    {
        // This would integrate with M-Pesa API
        // For now, create a placeholder record
        $payment->mpesaPayment()->create([
            'payment_id' => $payment->id,
            'mpesa_receipt_number' => $request->mpesa_receipt_number ?? '',
            'phone_number' => $request->phone_number ?? '',
            'transaction_date' => $request->transaction_date ?? date('Y-m-d H:i:s'),
            'amount' => $request->amount ?? 0.0,
        ]);
    }

    /**
     * Create bank payment details
     */
    private function createBankPayment($payment, $request)
    {
        // This would integrate with bank payment gateway
        // For now, create a placeholder record
        $payment->bankPayment()->create([
            'account_number' => $request->account_number ?? '',
            'bank_name' => $request->bank_name ?? '',
            'reference' => 'BANK_' . uniqid(),
            'status' => 'pending',
        ]);
    }

    /**
     * Process successful payment by adding tokens to wallet
     */
    private function processSuccessfulPayment($payment)
    {
        $wallet = Wallet::where('user_id', $payment->user_id)->first();

        if ($wallet) {
            $wallet->addTokens($payment->tokens);

            // Create token transaction record
            TokenTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'credit',
                'amount' => $payment->tokens,
                'description' => "Payment via {$payment->channel}",
                'reference' => $payment->reference ?? $payment->id,
            ]);
        }
    }

    private function generateReference()
    {

        $greatestId = Payment::max('id');
        return "CBC" . str_pad($greatestId + 1, 8, '0', STR_PAD_LEFT);
    }
}