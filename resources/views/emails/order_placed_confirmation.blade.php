<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order confirmed</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .container { background-color: #f5f7ff; border-radius: 12px; padding: 32px; margin: 20px 0; border: 1px solid #e2e8f0; }
        h1 { color: #273e8e; font-size: 22px; margin-top: 0; }
        .message { color: #444; margin: 20px 0; }
        .details { background: #fff; border-radius: 8px; padding: 16px 20px; margin: 16px 0; font-size: 14px; border: 1px solid #e2e8f0; }
        .details p { margin: 8px 0; }
        .btn { display: inline-block; background-color: #273e8e; color: #fff !important; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; margin: 16px 0; }
        .footer { margin-top: 28px; padding-top: 20px; border-top: 1px solid #cbd5e1; font-size: 12px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Your order is confirmed</h1>

        <p>Hello {{ trim($user->first_name . ' ' . $user->sur_name) }},</p>

        <div class="message">
            <p>Thank you for shopping with Troosolar. We have received your order and our team will contact you with delivery updates.</p>
            <p>You can review your order details anytime in your account.</p>
        </div>

        <div class="details">
            <p><strong>Order number:</strong> {{ $order->order_number ?? ('#' . $order->id) }}</p>
            <p><strong>Item:</strong> {{ $orderSummaryLine }}</p>
            @if(!empty($order->total_price))
                <p><strong>Order total:</strong> ₦{{ number_format((float) $order->total_price, 2) }}</p>
            @endif
        </div>

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
            <p>This message was sent because an order was placed on your Troosolar account.</p>
        </div>
    </div>
</body>
</html>
