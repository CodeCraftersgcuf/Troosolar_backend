<?php

namespace App\Mail;

use App\Models\LoanApplication;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BNPLStatusEmail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public LoanApplication $application;
    public string $status; // 'approved' | 'rejected' | 'counter_offer'
    public string $continueUrl;
    public string $subjectLine;
    public string $headingText;
    public string $bodyText;
    public ?float $downPayment;
    public ?int $repaymentDuration;
    public ?float $loanAmount;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, LoanApplication $application, string $status)
    {
        $this->user = $user;
        $this->application = $application;
        $this->status = $status;

        $frontendUrl = rtrim(config('app.frontend_url', 'https://troosolar.hmstech.org'), '/');
        $this->continueUrl = $frontendUrl . '/bnpl?applicationId=' . $application->id;

        $this->downPayment = $application->mono ? (float) $application->mono->down_payment : null;
        $this->repaymentDuration = (int) $application->repayment_duration;
        $this->loanAmount = (float) $application->loan_amount;

        if ($status === 'approved') {
            $this->subjectLine = 'Your BNPL Loan Application Has Been Approved – Troosolar';
            $this->headingText = 'Congratulations! Your loan has been approved';
            $this->bodyText = 'Your Buy Now Pay Later application has been approved. Please complete your down payment to confirm your order and proceed with your purchase.';
        } elseif ($status === 'counter_offer') {
            $this->subjectLine = 'You Have a Counter Offer on Your BNPL Application – Troosolar';
            $this->headingText = 'Counter offer on your BNPL application';
            $this->bodyText = 'We have sent you a counter offer on your BNPL application. Please log in to review the terms and accept or decline.';
        } else {
            $this->subjectLine = 'Update on Your BNPL Application – Troosolar';
            $this->headingText = 'Update on your BNPL application';
            $this->bodyText = 'We are unable to approve your BNPL application at this time. Thank you for your interest in Troosolar.';
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
            view: 'emails.bnpl_status',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
