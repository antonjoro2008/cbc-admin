<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuardianPerformanceReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $learner,
        public array $report
    ) {}

    public function build(): self
    {
        return $this->subject('Gravity CBC — Learner Assessment Report')
            ->view('emails.guardian-performance-report');
    }
}

