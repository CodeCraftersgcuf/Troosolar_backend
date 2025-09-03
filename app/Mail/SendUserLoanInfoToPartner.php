<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendUserLoanInfoToPartner extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $loanApplications;
    public $partner;
    public $linkAccount;
    /**
     * Create a new message instance.
     */
    public function __construct($user, $loanApplications, $partner, $linkAccount)
    {
        $this->user = $user;
        $this->loanApplications = $loanApplications;
        $this->partner = $partner;
        $this->linkAccount = $linkAccount;
    }
   

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Send User Loan Info To Partner',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user_loan_info',
        );
    }

    public function build()
    {
        return $this->subject('User and Loan Application Info')
                    ->markdown('emails.user_loan_info');
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
