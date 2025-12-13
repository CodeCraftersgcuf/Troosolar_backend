<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart Items - TrooSolar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #0066cc;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #0066cc;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .cart-items {
            background-color: white;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TrooSolar</h1>
        <h2>{{ $orderType === 'bnpl' ? 'Buy Now Pay Later' : 'Buy Now' }} Cart</h2>
    </div>
    
    <div class="content">
        <p>Dear {{ $user->first_name }},</p>
        
        @if($customMessage)
            <p><strong>Message from Admin:</strong></p>
            <p style="background-color: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin: 15px 0;">
                {{ $customMessage }}
            </p>
        @endif

        <p>We have prepared a custom order for you with the following items:</p>

        <div class="cart-items">
            @foreach($cartItems as $item)
                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                    <strong>{{ $item->itemable->title ?? 'Item' }}</strong><br>
                    Type: {{ class_basename($item->itemable_type) }}<br>
                    Quantity: {{ $item->quantity }}<br>
                    Unit Price: ₦{{ number_format($item->unit_price ?? 0, 2) }}<br>
                    Subtotal: ₦{{ number_format($item->subtotal ?? 0, 2) }}
                </div>
            @endforeach
            
            <div style="margin-top: 20px; padding-top: 15px; border-top: 2px solid #0066cc;">
                <strong>Total: ₦{{ number_format($cartItems->sum('subtotal'), 2) }}</strong>
            </div>
        </div>

        <p>Click the button below to view your cart and proceed with checkout:</p>
        
        <div style="text-align: center;">
            <a href="{{ $cartLink }}" class="button">View Cart & Checkout</a>
        </div>

        <p style="margin-top: 30px; font-size: 14px; color: #666;">
            If you're not logged in, you'll be prompted to log in first. After logging in, you'll be redirected to your cart automatically.
        </p>

        @if($orderType === 'bnpl')
            <p style="background-color: #d1ecf1; padding: 15px; border-left: 4px solid #0066cc; margin: 20px 0;">
                <strong>Buy Now Pay Later Option:</strong> You can choose to pay in installments with flexible payment plans. Minimum order value: ₦1,500,000
            </p>
        @endif
    </div>

    <div class="footer">
        <p>This is an automated message from TrooSolar.</p>
        <p>If you did not request this, please ignore this email.</p>
    </div>
</body>
</html>

