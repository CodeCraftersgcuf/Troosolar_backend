<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderDeliveredThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public User $user;

    /** Short description of what was ordered (one line for the email body). */
    public string $orderSummaryLine;

    /** Link to dashboard → More → My orders (leave a review). */
    public string $dashboardOrdersUrl;

    public function __construct(Order $order, User $user, string $orderSummaryLine)
    {
        $this->order = $order;
        $this->user = $user;
        $this->orderSummaryLine = $orderSummaryLine;
        $this->dashboardOrdersUrl = rtrim((string) config('app.frontend_url', 'https://app.troosolar.io'), '/').'/more?section=myOrders';
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your order has been delivered – thank you from Troosolar',
            replyTo: [config('mail.from.address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_delivered_thank_you',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
