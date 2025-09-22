<?php

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, SmsService;

    public $timeout = 60;
    public $tries = 3;

    protected $phoneNumber;
    protected $message;
    protected $from;

    /**
     * Create a new job instance.
     */
    public function __construct(string $phoneNumber, string $message, ?string $from = null)
    {
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
        $this->from = $from;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Check if system SMS is enabled
            if (!config('sms.send_system_sms', false)) {
                Log::info('System SMS is disabled. Skipping SMS send.', [
                    'phone' => $this->phoneNumber,
                    'message' => $this->message
                ]);
                return;
            }

            // Validate SMS configuration
            if (!$this->validateSmsConfig()) {
                Log::error('SMS configuration is invalid. Cannot send SMS.', [
                    'phone' => $this->phoneNumber,
                    'message' => $this->message
                ]);
                return;
            }

            // Send SMS
            $result = $this->sendSms($this->phoneNumber, $this->message, $this->from);

            if ($result['success']) {
                Log::info('SMS sent successfully via queue', [
                    'phone' => $this->phoneNumber,
                    'message' => $this->message,
                    'result' => $result
                ]);
            } else {
                Log::error('Failed to send SMS via queue', [
                    'phone' => $this->phoneNumber,
                    'message' => $this->message,
                    'error' => $result['error'] ?? 'Unknown error'
                ]);
                
                // Re-throw exception to trigger retry
                throw new \Exception($result['message'] ?? 'Failed to send SMS');
            }

        } catch (\Exception $e) {
            Log::error('SMS job failed', [
                'phone' => $this->phoneNumber,
                'message' => $this->message,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SMS job failed permanently', [
            'phone' => $this->phoneNumber,
            'message' => $this->message,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
