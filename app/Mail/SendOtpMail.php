<?php

namespace App\Mail;

use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;
    public $otp;
    // public $userotp;
    public $user;
    /**
     * Create a new message instance.
     */
    public function __construct( $otp, User $user)
    {
        $this->otp = $otp;
        // $this->userotp = $userotp;
        $this->user = $user;
    }

    // build function
    public function build()
{
    return $this->subject('Your OTP Code')
                ->view('emails.otp')
                ->with([
                    'otp' => $this->otp,
                    'first_name' => $this->user->first_name,
                ]);
}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Send Otp Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
        );
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
