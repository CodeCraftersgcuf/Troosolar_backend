<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPlacedConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public User $user;

    /** One-line description of the main item(s). */
    public string $orderSummaryLine;

    /** Deep link to this order in the customer dashboard. */
    public string $orderDetailUrl;

    public function __construct(Order $order, User $user, string $orderSummaryLine)
    {
        $this->order = $order;
        $this->user = $user;
        $this->orderSummaryLine = $orderSummaryLine;
        $base = rtrim((string) config('app.frontend_url', 'https://app.troosolar.io'), '/');
        $this->orderDetailUrl = $base.'/more?section=myOrders&orderId='.$order->id;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order confirmed — '.$this->order->order_number.' (Troosolar)',
            replyTo: [config('mail.from.address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_placed_confirmation',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
