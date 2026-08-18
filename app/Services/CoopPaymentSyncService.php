<?php

namespace App\Services;

use App\Models\CoopPayment;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class CoopPaymentSyncService
{
    public function __construct(
        private readonly CoopBankService $coopBank,
        private readonly PaymentFulfillmentService $fulfillment,
    ) {}

    public function refreshFromStatusEnquiry(Payment $payment): Payment
    {
        $coopPayment = $payment->coopPayment;

        if (! $coopPayment?->message_reference) {
            return $payment;
        }

        $payment->refresh();

        if ($payment->status === 'successful') {
            return $payment->load(['user.institution', 'coopPayment']);
        }

        $statusPayload = $this->coopBank->transactionStatus($coopPayment->message_reference);
        $this->applyEnquiryResult($coopPayment, $payment, $statusPayload);

        return $payment->fresh()->load(['user.institution', 'coopPayment']);
    }

    /**
     * Poll recent pending STK payments and expire ones that never completed.
     */
    public function syncPendingStk(int $maxAgeMinutes = 15): int
    {
        $synced = 0;

        $pending = CoopPayment::query()
            ->where('source', 'stk')
            ->where('status', 'pending')
            ->whereNotNull('message_reference')
            ->where('created_at', '>=', now()->subMinutes($maxAgeMinutes))
            ->with('payment')
            ->orderBy('id')
            ->limit(50)
            ->get();

        foreach ($pending as $coopPayment) {
            $payment = $coopPayment->payment;

            if (! $payment || $payment->status === 'successful') {
                continue;
            }

            try {
                $this->refreshFromStatusEnquiry($payment);
                $synced++;
            } catch (Throwable $e) {
                Log::error('Co-op STK status poll failed', [
                    'payment_id' => $payment->id,
                    'message_reference' => $coopPayment->message_reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->expireStalePending($maxAgeMinutes);

        return $synced;
    }

    private function applyEnquiryResult(CoopPayment $coopPayment, Payment $payment, array $payload): void
    {
        $outcome = $this->coopBank->interpretOutcome($payload);

        DB::transaction(function () use ($coopPayment, $payment, $payload, $outcome) {
            $coopPayment->refresh();
            $payment->refresh();

            $enquiries = $coopPayment->stk_response['status_enquiries'] ?? [];
            $enquiries[] = [
                'at' => now()->toIso8601String(),
                'payload' => $payload,
                'outcome' => $outcome,
            ];

            $stkResponse = $coopPayment->stk_response ?? [];
            $stkResponse['status_enquiry'] = $payload;
            $stkResponse['status_enquiries'] = array_slice($enquiries, -5);

            $coopPayment->update([
                'stk_response' => $stkResponse,
                'transaction_id' => $this->coopBank->extractTransactionId($payload) ?? $coopPayment->transaction_id,
                'status' => $outcome === 'pending' ? $coopPayment->status : $outcome,
            ]);

            if ($outcome === 'successful') {
                $this->fulfillment->creditIfPending($payment);
            } elseif ($outcome === 'failed' && $payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
            }
        });
    }

    private function expireStalePending(int $maxAgeMinutes): void
    {
        $stale = CoopPayment::query()
            ->where('source', 'stk')
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes($maxAgeMinutes))
            ->with('payment')
            ->limit(50)
            ->get();

        foreach ($stale as $coopPayment) {
            $payment = $coopPayment->payment;

            if ($payment && $payment->status === 'successful') {
                $coopPayment->update(['status' => 'successful']);

                continue;
            }

            $coopPayment->update(['status' => 'failed']);

            if ($payment && $payment->status === 'pending') {
                $payment->update(['status' => 'failed']);
            }
        }
    }
}
