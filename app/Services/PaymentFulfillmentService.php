<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentFulfillmentService
{
    public function creditIfPending(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $locked = Payment::query()->where('id', $payment->id)->lockForUpdate()->first();

            if (! $locked || $locked->status === 'successful') {
                return false;
            }

            $wallet = Wallet::where('user_id', $locked->user_id)->first();

            if (! $wallet) {
                Log::error('Wallet not found for successful payment', [
                    'payment_id' => $locked->id,
                    'user_id' => $locked->user_id,
                ]);

                $locked->update(['status' => 'successful']);

                return false;
            }

            $minutesPerToken = (float) Setting::getValue('minutes_per_token', 1.0);
            $minutesToCredit = (float) $locked->tokens * $minutesPerToken;

            $wallet->addTokensAndMinutes((float) $locked->tokens, $minutesToCredit);

            TokenTransaction::create([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'credit',
                'tokens' => $locked->tokens,
                'reference' => $locked->reference ?? (string) $locked->id,
                'description' => "Payment via {$locked->channel} - {$locked->tokens} tokens, {$minutesToCredit} minutes",
            ]);

            $locked->update(['status' => 'successful']);

            $user = User::find($locked->user_id);
            if ($user) {
                SmsNotificationService::sendPaymentSuccessSms($user, (int) $locked->tokens, (float) $locked->amount);
            }

            Log::info('Payment credited', [
                'payment_id' => $locked->id,
                'tokens' => $locked->tokens,
                'minutes' => $minutesToCredit,
            ]);

            return true;
        });
    }
}
