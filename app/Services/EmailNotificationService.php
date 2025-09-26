<?php

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /**
     * Send password reset email
     */
    public static function sendPasswordResetEmail(User $user, string $resetCode): void
    {
        if (!$user->email) {
            Log::info('No email address for user, skipping email notification', [
                'user_id' => $user->id,
                'phone' => $user->phone_number
            ]);
            return;
        }

        try {
            $subject = 'CBC Admin - Password Reset Code';
            $message = self::getPasswordResetEmailTemplate($user, $resetCode);

            // Dispatch email job to queue
            SendEmailJob::dispatch($user->email, $subject, $message);

            Log::info('Password reset email queued', [
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => $resetCode
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to queue password reset email', [
                'user_id' => $user->id,
                'email' => $user->email,
                'code' => $resetCode,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send custom email
     */
    public static function sendCustomEmail(string $to, string $subject, string $message, ?string $from = null): void
    {
        try {
            // Dispatch email job to queue
            SendEmailJob::dispatch($to, $subject, $message, $from);

            Log::info('Custom email queued', [
                'to' => $to,
                'subject' => $subject
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to queue custom email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get password reset email template
     */
    private static function getPasswordResetEmailTemplate(User $user, string $resetCode): string
    {
        return "Dear {$user->name},\n\n" .
               "You have requested to reset your password for your Gravity CBC account.\n\n" .
               "Your password reset code is: {$resetCode}\n\n" .
               "This code will expire in 15 minutes for security reasons.\n\n" .
               "If you did not request this password reset, please ignore this email.\n\n" .
               "Best regards,\n" .
               "Gravity CBC Team";
    }
}
