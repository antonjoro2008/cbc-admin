<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoopPayment;
use App\Models\Payment;
use App\Services\CoopBankService;
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
    ) {}

    /**
     * STK result callback from Co-operative Bank.
     */
    public function stkCallback(Request $request): JsonResponse
    {
        if (! $this->callbackTokenIsValid($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized callback',
            ], 403);
        }

        $payload = $request->all();
        Log::info('Co-op STK callback received', $payload);

        $messageReference = $this->coopBank->extractMessageReference($payload);
        $coopPayment = $messageReference
            ? CoopPayment::where('message_reference', $messageReference)->first()
            : null;

        if (! $coopPayment) {
            Log::warning('Co-op STK callback could not be matched', [
                'message_reference' => $messageReference,
                'payload' => $payload,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Callback received',
            ]);
        }

        $outcome = $this->coopBank->interpretOutcome($payload);

        DB::transaction(function () use ($coopPayment, $payload, $outcome) {
            $coopPayment->refresh();
            $coopPayment->update([
                'callback_payload' => $payload,
                'transaction_id' => $this->coopBank->extractTransactionId($payload) ?? $coopPayment->transaction_id,
                'phone_number' => $this->coopBank->extractPhoneNumber($payload) ?? $coopPayment->phone_number,
                'amount' => $this->coopBank->extractAmount($payload) ?? $coopPayment->amount,
                'status' => $outcome === 'pending' ? $coopPayment->status : $outcome,
                'source' => 'stk',
                'transaction_date' => now(),
            ]);

            $payment = $coopPayment->payment;
            if (! $payment) {
                return;
            }

            if ($outcome === 'successful') {
                $this->fulfillment->creditIfPending($payment);
            } elseif ($outcome === 'failed' && $payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
            }
        });

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

        if (CoopPayment::where('transaction_id', $transactionId)->exists()) {
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

        $statusPayload = $this->coopBank->transactionStatus($coopPayment->message_reference);
        $outcome = $this->coopBank->interpretOutcome($statusPayload);

        DB::transaction(function () use ($coopPayment, $payment, $statusPayload, $outcome) {
            $coopPayment->update([
                'stk_response' => array_merge($coopPayment->stk_response ?? [], ['status_enquiry' => $statusPayload]),
                'transaction_id' => $this->coopBank->extractTransactionId($statusPayload) ?? $coopPayment->transaction_id,
                'status' => $outcome === 'pending' ? $coopPayment->status : $outcome,
            ]);

            if ($outcome === 'successful') {
                $this->fulfillment->creditIfPending($payment);
            } elseif ($outcome === 'failed' && $payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Payment status refreshed',
            'data' => $payment->fresh()->load(['user.institution', 'coopPayment']),
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
