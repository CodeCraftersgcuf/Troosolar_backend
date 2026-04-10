<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;

    public User $user;

    /** Human-readable previous status (e.g. "Pending"). */
    public string $previousStatusHuman;

    /** Human-readable new status (e.g. "Shipped"). */
    public string $newStatusHuman;

    /** Short one-line summary of the purchase. */
    public string $orderSummaryLine;

    public string $dashboardOrdersUrl;

    public function __construct(
        Order $order,
        User $user,
        string $previousStatusHuman,
        string $newStatusHuman,
        string $orderSummaryLine
    ) {
        $this->order = $order;
        $this->user = $user;
        $this->previousStatusHuman = $previousStatusHuman;
        $this->newStatusHuman = $newStatusHuman;
        $this->orderSummaryLine = $orderSummaryLine;
        $this->dashboardOrdersUrl = rtrim((string) config('app.frontend_url', 'https://app.troosolar.io'), '/').'/more?section=myOrders';
    }

    public function envelope(): Envelope
    {
        $new = strtolower((string) ($this->order->order_status ?? ''));
        $subject = in_array($new, ['delivered', 'completed'], true)
            ? 'Your order has been delivered – thank you from Troosolar'
            : sprintf(
                'Order %s update: %s',
                $this->order->order_number ?? ('#'.$this->order->id),
                $this->newStatusHuman
            );

        return new Envelope(
            subject: $subject,
            replyTo: [config('mail.from.address')],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order_status_updated',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
