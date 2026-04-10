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
@php
    $items = isset($orderView['items']) && is_array($orderView['items']) ? $orderView['items'] : [];
    $totalPaid = (float) ($orderView['total_price'] ?? $order->total_price ?? 0);
    $itemsSub = isset($orderView['items_subtotal']) ? (float) $orderView['items_subtotal'] : null;
    $catalogSub = isset($orderView['catalog_items_subtotal']) ? (float) $orderView['catalog_items_subtotal'] : null;
    $checkoutDisc = isset($orderView['online_checkout_discount_amount']) ? (float) $orderView['online_checkout_discount_amount'] : null;
    $deliveryFee = isset($orderView['delivery_fee']) ? (float) $orderView['delivery_fee'] : 0;
    $installPrice = isset($orderView['installation_price']) ? (float) $orderView['installation_price'] : 0;
    $insuranceFee = isset($orderView['insurance_fee']) ? (float) $orderView['insurance_fee'] : 0;
    $vatAmt = isset($orderView['vat_amount']) ? (float) $orderView['vat_amount'] : 0;
    $vatPct = isset($orderView['vat_percentage']) ? (float) $orderView['vat_percentage'] : 0;
@endphp
    <div class="container">
        <h1>Your order is confirmed</h1>

        <p>Hello {{ trim($user->first_name . ' ' . $user->sur_name) }},</p>

        <div class="message">
            <p>Thank you for shopping with Troosolar. We have received your order and payment. Our team will contact you with delivery updates.</p>
            <p>You can review your full order details anytime in your account.</p>
        </div>

        <div class="details">
            <p><strong>Order number:</strong> {{ $order->order_number ?? ('#' . $order->id) }}</p>
            @if(!empty($order->created_at))
                <p class="muted">Placed: {{ $order->created_at instanceof \Carbon\CarbonInterface ? $order->created_at->format('l j F Y, H:i') : $order->created_at }}</p>
            @endif

            @if(count($items) > 0)
                <p style="margin-top: 16px;"><strong>Items ordered</strong></p>
                <table class="items" role="presentation">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Unit</th>
                            <th style="text-align: right;">Line total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $row)
                            @php
                                $title = $row['item']['title'] ?? $row['item']['name'] ?? 'Item';
                                $sub = $row['item']['subtitle'] ?? null;
                                $qty = (int) ($row['quantity'] ?? 1);
                                $unit = (float) ($row['unit_price'] ?? 0);
                                $lineSub = (float) ($row['subtotal'] ?? 0);
                                $listUnit = isset($row['list_unit_price']) ? (float) $row['list_unit_price'] : null;
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $title }}</strong>
                                    @if(!empty($sub))
                                        <br><span class="muted">{{ $sub }}</span>
                                    @endif
                                    @if($listUnit !== null && $listUnit > $unit + 0.005)
                                        <br><span class="muted">List price: ₦{{ number_format($listUnit, 2) }} → you paid ₦{{ number_format($unit, 2) }}/unit</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">{{ $qty }}</td>
                                <td style="text-align: right;">₦{{ number_format($unit, 2) }}</td>
                                <td style="text-align: right;"><strong>₦{{ number_format($lineSub, 2) }}</strong></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p><strong>Items:</strong> Your Troosolar purchase (see total below).</p>
            @endif
        </div>

        <div class="details">
            <p><strong>Payment summary</strong></p>
            @if($checkoutDisc !== null && $checkoutDisc > 0.005 && $catalogSub !== null && $catalogSub > 0)
                <p><span style="color:#64748b;">Items subtotal (before discount)</span><br><strong>₦{{ number_format($catalogSub, 2) }}</strong></p>
                <p class="discount"><span>Online checkout discount</span><br><strong>−₦{{ number_format($checkoutDisc, 2) }}</strong></p>
                <p><span style="color:#64748b;">Items after discount</span><br><strong>₦{{ number_format($itemsSub ?? ($catalogSub - $checkoutDisc), 2) }}</strong></p>
            @elseif($itemsSub !== null && $itemsSub > 0)
                <p><span style="color:#64748b;">Items subtotal</span><br><strong>₦{{ number_format($itemsSub, 2) }}</strong></p>
            @endif
            <p><span style="color:#64748b;">Delivery</span><br><strong>{{ $deliveryFee > 0 ? '₦'.number_format($deliveryFee, 2) : 'Free' }}</strong></p>
            @if($installPrice > 0.005)
                <p><span style="color:#64748b;">Installation</span><br><strong>₦{{ number_format($installPrice, 2) }}</strong></p>
            @endif
            @if($insuranceFee > 0.005)
                <p><span style="color:#64748b;">Insurance</span><br><strong>₦{{ number_format($insuranceFee, 2) }}</strong></p>
            @endif
            @if($vatAmt > 0.005)
                <p><span style="color:#64748b;">VAT{{ $vatPct > 0 ? ' ('.$vatPct.'%)' : '' }}</span><br><strong>₦{{ number_format($vatAmt, 2) }}</strong></p>
            @endif
            <p class="amount-paid">
                Amount paid (incl. VAT)<br>
                ₦{{ number_format($totalPaid, 2) }}
            </p>
            @if(!empty($orderView['payment_method']))
                <p class="muted" style="margin-top:8px;">Payment method: {{ ucfirst((string) $orderView['payment_method']) }}</p>
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
