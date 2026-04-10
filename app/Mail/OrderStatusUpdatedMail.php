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

    /**
     * Formatted order from OrderController::formatOrder() (all line items + totals).
     *
     * @var array<string, mixed>
     */
    public array $orderView;

    /** Deep link to this order in the customer dashboard. */
    public string $orderDetailUrl;

    /**
     * @param  array<string, mixed>  $orderView
     */
    public function __construct(
        Order $order,
        User $user,
        string $previousStatusHuman,
        string $newStatusHuman,
        array $orderView
    ) {
        $this->order = $order;
        $this->user = $user;
        $this->previousStatusHuman = $previousStatusHuman;
        $this->newStatusHuman = $newStatusHuman;
        $this->orderView = $orderView;
        $base = rtrim((string) config('app.frontend_url', 'https://app.troosolar.io'), '/');
        $this->orderDetailUrl = $base.'/more?section=myOrders&orderId='.$order->id;
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
