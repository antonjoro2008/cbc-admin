<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoopPayment;
use App\Models\Payment;
use App\Services\CoopBankService;
use App\Services\CoopPaymentSyncService;
use App\Services\PaymentFulfillmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoopPaymentController extends Controller
{
    public function __construct(
        private readonly CoopBankService $coopBank,
        private readonly PaymentFulfillmentService $fulfillment,
        private readonly CoopPaymentSyncService $stkSync,
    ) {}

    /**
     * Co-op confirmed they will not send STK callbacks. This endpoint only
     * acknowledges an unexpected post so their gateway does not retry.
     */
    public function stkCallback(Request $request): JsonResponse
    {
        if (! $this->callbackTokenIsValid($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized callback',
            ], 403);
        }

        Log::info('Co-op STK callback received (ignored; status enquiry is the source of truth)', $request->all());

        return response()->json([
            'success' => true,
            'message' => 'Callback received',
        ]);
    }

    /**
     * B2B Instant Payment Notification from Co-operative Bank.
     */
    public function ipn(Request $request): JsonResponse
    {
        $payload = $request->all();
        Log::info('Co-op IPN received', $payload);

        $transactionId = trim((string) ($payload['TransactionId'] ?? ''));

        if ($transactionId === '') {
            return $this->ipnFailure('TransactionId is required');
        }

        $existingByTransaction = CoopPayment::where('transaction_id', $transactionId)->first();
        if ($existingByTransaction) {
            $payment = $existingByTransaction->payment;
            $eventType = strtoupper(trim((string) ($payload['EventType'] ?? '')));

            if ($payment && $eventType === 'CREDIT' && $payment->status !== 'successful') {
                DB::transaction(function () use ($payment, $existingByTransaction, $payload) {
                    $this->fulfillment->creditIfPending($payment);
                    $existingByTransaction->update([
                        'ipn_payload' => $payload,
                        'status' => 'successful',
                    ]);
                });
            }

            return $this->ipnSuccess('Duplicate notification ignored');
        }

        $eventType = strtoupper(trim((string) ($payload['EventType'] ?? '')));
        $amount = (float) ($payload['Amount'] ?? 0);
        $accountNumber = (string) ($payload['AcctNo'] ?? '');
        $expectedAccount = (string) config('services.coop.account_number');

        $matchedPayment = $this->matchPaymentFromIpn($payload);

        $coopPayment = $matchedPayment
            ? CoopPayment::query()
                ->where(function ($query) use ($matchedPayment) {
                    $query->where('payment_id', $matchedPayment->id)
                        ->orWhere('message_reference', $matchedPayment->reference);
                })
                ->first()
            : null;

        $ipnAttributes = [
            'payment_id' => $matchedPayment?->id ?? $coopPayment?->payment_id,
            'message_reference' => $matchedPayment?->reference ?? $coopPayment?->message_reference,
            'transaction_id' => $transactionId,
            'payment_ref' => $payload['PaymentRef'] ?? null,
            'account_number' => $accountNumber,
            'event_type' => $eventType ?: null,
            'narration' => $payload['Narration'] ?? null,
            'amount' => $amount ?: ($coopPayment?->amount ?? 0),
            'currency' => $payload['Currency'] ?? $coopPayment?->currency ?? 'KES',
            'status' => $matchedPayment ? 'pending' : 'unmatched',
            'source' => $coopPayment?->source === 'stk' ? 'stk' : 'ipn',
            'ipn_payload' => $payload,
            'transaction_date' => $this->parseIpnDate($payload),
        ];

        if ($coopPayment) {
            $coopPayment->update($ipnAttributes);
        } else {
            $coopPayment = CoopPayment::create($ipnAttributes);
        }

        if ($expectedAccount !== '' && $accountNumber !== '' && $accountNumber !== $expectedAccount) {
            Log::warning('Co-op IPN account number mismatch', [
                'expected' => $expectedAccount,
                'received' => $accountNumber,
                'transaction_id' => $transactionId,
            ]);

            return $this->ipnSuccess('Notification stored');
        }

        if ($eventType !== 'CREDIT') {
            $coopPayment->update(['status' => 'ignored']);

            return $this->ipnSuccess('Non-credit event stored');
        }

        if (! $matchedPayment) {
            return $this->ipnSuccess('Unmatched credit stored for reconciliation');
        }

        DB::transaction(function () use ($matchedPayment, $coopPayment) {
            $this->fulfillment->creditIfPending($matchedPayment);
            $coopPayment->update(['status' => 'successful']);
        });

        return $this->ipnSuccess('Successfully received data');
    }

    /**
     * Ask Co-op for the current STK status and credit if completed.
     */
    public function syncStatus(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && $payment->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied to this payment',
            ], 403);
        }

        $coopPayment = $payment->coopPayment;
        if (! $coopPayment || ! $coopPayment->message_reference) {
            return response()->json([
                'success' => false,
                'message' => 'This payment was not initiated via Co-op STK',
            ], 422);
        }

        if ($payment->status === 'successful') {
            return response()->json([
                'success' => true,
                'message' => 'Payment already successful',
                'data' => $payment->fresh()->load(['user.institution', 'coopPayment']),
            ]);
        }

        try {
            $payment = $this->stkSync->refreshFromStatusEnquiry($payment);
        } catch (\Throwable $e) {
            Log::error('Co-op STK status sync failed', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment is still pending confirmation',
                'data' => $payment->fresh()->load(['user.institution', 'coopPayment']),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment status refreshed',
            'data' => $payment,
        ]);
    }

    private function callbackTokenIsValid(Request $request): bool
    {
        $expected = (string) config('services.coop.callback_token');

        if ($expected === '') {
            return true;
        }

        $provided = (string) ($request->query('token') ?: $request->header('X-Coop-Callback-Token', ''));

        return hash_equals($expected, $provided);
    }

    private function matchPaymentFromIpn(array $payload): ?Payment
    {
        $candidates = array_filter([
            $payload['PaymentRef'] ?? null,
            $payload['Narration'] ?? null,
            $payload['CustMemoLine1'] ?? null,
            $payload['CustMemoLine2'] ?? null,
            $payload['CustMemoLine3'] ?? null,
        ], fn ($value) => is_string($value) && trim($value) !== '');

        foreach ($candidates as $candidate) {
            $payment = Payment::where('reference', trim($candidate))->first();
            if ($payment) {
                return $payment;
            }
        }

        foreach ($candidates as $candidate) {
            if (preg_match('/CBC\d{8}/i', $candidate, $matches)) {
                $payment = Payment::where('reference', strtoupper($matches[0]))->first();
                if ($payment) {
                    return $payment;
                }
            }
        }

        return null;
    }

    private function parseIpnDate(array $payload): ?\Carbon\Carbon
    {
        $raw = $payload['TransactionDate'] ?? $payload['PostingDate'] ?? $payload['ValueDate'] ?? null;

        if (! $raw) {
            return now();
        }

        try {
            return \Carbon\Carbon::parse($raw);
        } catch (\Throwable) {
            return now();
        }
    }

    private function ipnSuccess(string $message): JsonResponse
    {
        return response()->json([
            'MessageCode' => '200',
            'Message' => $message,
        ], 200);
    }

    private function ipnFailure(string $message): JsonResponse
    {
        return response()->json([
            'MessageCode' => '400',
            'Message' => $message,
        ], 400);
    }
}
