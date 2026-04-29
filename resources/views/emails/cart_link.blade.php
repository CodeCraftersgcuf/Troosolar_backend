<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $headline }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .container { background-color: #f5f7ff; border-radius: 12px; padding: 32px; margin: 20px 0; border: 1px solid #e2e8f0; }
        h1 { color: #273e8e; font-size: 22px; margin-top: 0; }
        .message { color: #444; margin: 20px 0; }
        .details { background: #fff; border-radius: 8px; padding: 16px 20px; margin: 16px 0; font-size: 14px; border: 1px solid #e2e8f0; }
        .details p { margin: 8px 0; }
        .item-list { margin: 12px 0 0 0; padding-left: 0; list-style: none; }
        .item-list li { margin: 6px 0; padding-left: 0; }
        .btn { display: inline-block; background-color: #273e8e; color: #ffffff !important; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 600; margin: 16px 0; }
        .footer { margin-top: 28px; padding-top: 20px; border-top: 1px solid #cbd5e1; font-size: 12px; color: #64748b; text-align: center; }
        .muted { color: #64748b; font-size: 13px; }
        .admin-note { background: #e8eefb; color: #0f172a; border-left: 4px solid #273e8e; padding: 14px 16px; margin: 16px 0; border-radius: 8px; font-size: 14px; }
        .info-strip { background-color: #e8eefb; color: #1e293b; border-left: 4px solid #273e8e; padding: 12px 14px; margin: 16px 0; font-size: 14px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $headline }}</h1>

        <p>Hello {{ trim(($user->first_name ?? '') . ' ' . ($user->sur_name ?? '')) }},</p>

        <div class="message">
            @if($orderType === 'bnpl')
                <p>Our team has added items to your Troosolar account for a <strong>Buy Now Pay Later</strong> application. Use the button below to sign in (if needed) and continue where you left off.</p>
            @else
                <p>Our team has prepared a custom <strong>Buy Now</strong> order in your Troosolar account. Use the button below to sign in (if needed) and complete checkout.</p>
            @endif
            <p>If you have questions, reply to this email or use the Help section in your account.</p>
        </div>

        @if(!empty($customMessage))
            <div class="admin-note">
                <p style="margin: 0 0 8px 0;"><strong>Message from our team</strong></p>
                <div style="color: #0f172a;">{!! nl2br(e($customMessage)) !!}</div>
            </div>
        @endif

        @php
            $itemsCollection = is_array($cartItems) ? collect($cartItems) : $cartItems;
        @endphp

        <div class="details">
            <p><strong>Reference:</strong> Custom order (admin)</p>

            @if($itemsCollection->count() > 0)
                <p style="margin-top: 16px; margin-bottom: 4px;"><strong>{{ $itemsCollection->count() === 1 ? 'Item' : 'Items' }}</strong></p>
                <ul class="item-list">
                    @foreach($itemsCollection as $item)
                        @php
                            $title = $item->itemable->title ?? $item->itemable->name ?? 'Item';
                            $qty = max(1, (int) ($item->quantity ?? 1));
                        @endphp
                        <li>
                            <strong>{{ $title }}</strong>
                            <span class="muted"> — Qty {{ $qty }}</span>
                            <br><span class="muted">Subtotal: ₦{{ number_format((float) ($item->subtotal ?? 0), 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if(!empty($customItemsForDisplay))
                <p style="margin-top: 16px; margin-bottom: 4px;"><strong>Custom products / services</strong></p>
                <ul class="item-list">
                    @foreach($customItemsForDisplay as $row)
                        @php
                            $nm = trim((string) ($row['name'] ?? ''));
                            $ds = trim((string) ($row['description'] ?? ''));
                            $pr = (float) ($row['price'] ?? 0);
                            $qt = max(1, (int) ($row['quantity'] ?? 1));
                            $ln = round($pr * $qt, 2);
                        @endphp
                        <li>
                            <strong>{{ $nm }}</strong>
                            @if($ds !== '')
                                <br><span class="muted">{{ $ds }}</span>
                            @endif
                            <br><span class="muted">₦{{ number_format($pr, 2) }} × {{ $qt }} = ₦{{ number_format($ln, 2) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if($itemsCollection->count() === 0 && empty($customItemsForDisplay))
                <p style="margin-top: 12px;"><strong>Item:</strong> See message above for details.</p>
            @endif

            <p style="margin-top: 16px;"><strong>Estimated total:</strong> ₦{{ number_format((float) $summaryTotal, 2) }}</p>
        </div>

        <p>
            <a href="{{ $cartLink }}" class="btn" target="_blank" rel="noopener noreferrer">{{ $ctaLabel }}</a>
        </p>
        <p style="font-size: 14px; color: #64748b;">
            Or copy this link into your browser:<br>
            <a href="{{ $cartLink }}" style="word-break: break-all; color: #273e8e;">{{ $cartLink }}</a>
        </p>

        @if($orderType === 'bnpl')
            <p class="info-strip">
                <strong>Buy Now Pay Later:</strong> You will continue in the BNPL flow (product selection, invoice, loan calculator, and application steps). Minimum order value rules apply as shown in the app.
            </p>
        @endif

        <div class="footer">
            <p>This message was sent because a Troosolar administrator prepared an order or cart for your account.</p>
        </div>
    </div>
</body>
</html>
