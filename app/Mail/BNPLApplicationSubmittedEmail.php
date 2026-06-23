<?php

namespace App\Mail;

use App\Models\LoanApplication;
use App\Models\User;
use App\Support\FrontendUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BNPLApplicationSubmittedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public LoanApplication $application;
    public string $applicationUrl;

    public function __construct(User $user, LoanApplication $application)
    {
        $this->user = $user;
        $this->application = $application;
        $this->applicationUrl = FrontendUrl::bnplApplicationTrack($application->id);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'BNPL Application Submitted - Troosolar',
            replyTo: [config('mail.from.address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.bnpl_application_submitted',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

