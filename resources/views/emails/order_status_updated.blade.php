@php
    $newSlug = strtolower((string) ($order->order_status ?? ''));
    $isDelivered = in_array($newSlug, ['delivered', 'completed'], true);
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order status update</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .container { background-color: #f5f7ff; border-radius: 12px; padding: 32px; margin: 20px 0; border: 1px solid #e2e8f0; }
        h1 { color: #273e8e; font-size: 22px; margin-top: 0; }
        .message { color: #444; margin: 20px 0; }
        .details { background: #fff; border-radius: 8px; padding: 16px 20px; margin: 16px 0; font-size: 14px; border: 1px solid #e2e8f0; }
        .details p { margin: 8px 0; }
        .status-badge { display: inline-block; background: #273e8e; color: #fff; padding: 6px 14px; border-radius: 999px; font-weight: 600; font-size: 14px; }
        table.items { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 12px; }
        table.items th, table.items td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        table.items th { color: #64748b; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.03em; }
        .amount-paid { font-size: 18px; font-weight: 700; color: #273e8e; margin-top: 12px; padding-top: 12px; border-top: 2px solid #273e8e; }
        .btn { display: inline-block; background-color: #273e8e; color: #fff !important; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; margin: 16px 0; }
        .footer { margin-top: 28px; padding-top: 20px; border-top: 1px solid #cbd5e1; font-size: 12px; color: #64748b; text-align: center; }
        .muted { color: #64748b; font-size: 12px; }
        .discount { color: #b45309; }
    </style>
</head>
<body>
    <div class="container">
        @if($isDelivered)
            <h1>Thank you — your order is delivered</h1>
        @else
            <h1>Your order status has been updated</h1>
        @endif

        <p>Hello {{ trim($user->first_name . ' ' . $user->sur_name) }},</p>

        <div class="message">
            @if($isDelivered)
                <p>Great news: your Troosolar order has been marked as <strong>delivered</strong>. We hope everything arrived safely and that you are happy with your purchase.</p>
                <p>If you have a moment, we would love to hear from you. Your feedback helps other customers and helps us improve.</p>
                <p style="font-size: 14px; color: #444;">Below is your full order: every item, discounts, delivery, VAT, and amount paid — matching your order confirmation.</p>
            @else
                <p>We have updated your order. The status is now:</p>
                <p><span class="status-badge">{{ $newStatusHuman }}</span></p>
                @if($previousStatusHuman !== '' && $previousStatusHuman !== '—' && strcasecmp($previousStatusHuman, $newStatusHuman) !== 0)
                    <p style="font-size: 14px; color: #64748b;">Previous status: {{ $previousStatusHuman }}</p>
                @endif
                <p style="font-size: 14px; color: #444;">Below is a full recap of your order and payment — the same detail as your confirmation email.</p>
            @endif
        </div>

        @include('emails.partials.order_email_line_items_and_payment', ['order' => $order, 'orderView' => $orderView])

        <p>
            <a href="{{ $orderDetailUrl }}" class="btn" target="_blank" rel="noopener noreferrer">See order details</a>
        </p>
        <p style="font-size: 14px; color: #64748b;">
            Or copy this link into your browser:<br>
            <a href="{{ $orderDetailUrl }}" style="word-break: break-all; color: #273e8e;">{{ $orderDetailUrl }}</a>
        </p>

        <p class="message" style="font-size: 14px;">
            If you have questions, reply to this email or use the Help section in your account.
        </p>

        <div class="footer">
            <p>This message was sent because your order status was updated. If this was unexpected, please contact support.</p>
        </div>
    </div>
</body>
</html>
