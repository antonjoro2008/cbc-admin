<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;

    protected $to;
    protected $subject;
    protected $message;
    protected $from;

    /**
     * Create a new job instance.
     */
    public function __construct(string $to, string $subject, string $message, ?string $from = null)
    {
        $this->to = $to;
        $this->subject = $subject;
        $this->message = $message;
        $this->from = $from;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Send email using Laravel's Mail facade
            Mail::raw($this->message, function ($mail) {
                $mail->to($this->to)
                     ->subject($this->subject);
                
                if ($this->from) {
                    $mail->from($this->from);
                }
            });

            Log::info('Email sent successfully via queue', [
                'to' => $this->to,
                'subject' => $this->subject
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send email via queue', [
                'to' => $this->to,
                'subject' => $this->subject,
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
        Log::error('Email job failed permanently', [
            'to' => $this->to,
            'subject' => $this->subject,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
