<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Log;

class SmsNotificationService
{
    /**
     * Send registration SMS
     */
    public static function sendRegistrationSms(User $user): void
    {
        if (!config('sms.send_system_sms', false)) {
            return;
        }

        try {
            $template = config('sms.templates.registration');
            $message = self::replacePlaceholders($template, [
                'name' => $user->name,
                'phone' => $user->phone_number,
                'user_type' => ucfirst($user->user_type)
            ]);

            // Dispatch SMS job to queue
            SendSmsJob::dispatch($user->phone_number, $message);

            Log::info('Registration SMS queued', [
                'user_id' => $user->id,
                'phone' => $user->phone_number,
                'user_type' => $user->user_type
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to queue registration SMS', [
                'user_id' => $user->id,
                'phone' => $user->phone_number,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment success SMS
     */
    public static function sendPaymentSuccessSms(User $user, int $tokens, float $amount): void
    {
        if (!config('sms.send_system_sms', false)) {
            return;
        }

        try {
            // Get current wallet balance
            $wallet = $user->getEffectiveWallet();
            $currentBalance = $wallet ? $wallet->balance : 0;

            $template = config('sms.templates.payment_success');
            $message = self::replacePlaceholders($template, [
                'name' => $user->name,
                'phone' => $user->phone_number,
                'tokens' => $tokens,
                'amount' => number_format($amount, 2),
                'balance' => $currentBalance
            ]);

            // Dispatch SMS job to queue
            SendSmsJob::dispatch($user->phone_number, $message);

            Log::info('Payment success SMS queued', [
                'user_id' => $user->id,
                'phone' => $user->phone_number,
                'tokens' => $tokens,
                'amount' => $amount,
                'balance' => $currentBalance
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to queue payment success SMS', [
                'user_id' => $user->id,
                'phone' => $user->phone_number,
                'tokens' => $tokens,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send password reset SMS
     */
    public static function sendPasswordResetSms(User $user, string $resetCode): void
    {
        if (!config('sms.send_system_sms', false)) {
            return;
        }

        try {
            $template = config('sms.templates.password_reset');
            $message = self::replacePlaceholders($template, [
                'name' => $user->name,
                'phone' => $user->phone_number,
                'code' => $resetCode
            ]);

            // Dispatch SMS job to queue
            SendSmsJob::dispatch($user->phone_number, $message);

            Log::info('Password reset SMS queued', [
                'user_id' => $user->id,
                'phone' => $user->phone_number,
                'code' => $resetCode
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to queue password reset SMS', [
                'user_id' => $user->id,
                'phone' => $user->phone_number,
                'code' => $resetCode,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Replace placeholders in SMS template
     */
    private static function replacePlaceholders(string $template, array $data): string
    {
        $message = $template;
        
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', $value, $message);
        }

        return $message;
    }

    /**
     * Send custom SMS (for manual use)
     */
    public static function sendCustomSms(string $phoneNumber, string $message, ?string $from = null): void
    {
        if (!config('sms.send_system_sms', false)) {
            return;
        }

        try {
            // Dispatch SMS job to queue
            SendSmsJob::dispatch($phoneNumber, $message, $from);

            Log::info('Custom SMS queued', [
                'phone' => $phoneNumber,
                'message' => $message,
                'from' => $from
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to queue custom SMS', [
                'phone' => $phoneNumber,
                'message' => $message,
                'error' => $e->getMessage()
            ]);
        }
    }
}
