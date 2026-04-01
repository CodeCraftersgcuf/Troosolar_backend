<?php

namespace App\Mail;

use App\Models\AuditRequest;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AuditStatusEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public AuditRequest $auditRequest;
    public string $subjectLine;
    public string $headingText;
    public string $bodyText;
    public string $status;

    public function __construct(User $user, AuditRequest $auditRequest, string $status)
    {
        $this->user = $user;
        $this->auditRequest = $auditRequest;
        $this->status = $status;

        if ($status === 'approved') {
            $this->subjectLine = 'Your audit request has been approved - Troosolar';
            $this->headingText = 'Your audit request has been approved';
            $this->bodyText = 'Your audit request has been approved. Our team will contact you to confirm the next steps and scheduling.';
        } elseif ($status === 'rejected') {
            $this->subjectLine = 'Update on your audit request - Troosolar';
            $this->headingText = 'Update on your audit request';
            $this->bodyText = 'We are unable to approve your audit request at this time. Please contact support if you need help.';
        } else {
            $this->subjectLine = 'Update on your audit request - Troosolar';
            $this->headingText = 'Update on your audit request';
            $this->bodyText = 'Your audit request status has been updated.';
        }
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
            replyTo: [config('mail.from.address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.audit_status',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
