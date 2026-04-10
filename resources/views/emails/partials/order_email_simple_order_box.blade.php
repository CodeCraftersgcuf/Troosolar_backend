{{-- Minimal summary: order number, all line items, order total (no payment breakdown). Expects: $order, $orderView --}}
@php
    $items = isset($orderView['items']) && is_array($orderView['items']) ? $orderView['items'] : [];
    $orderTotal = (float) ($orderView['total_price'] ?? $order->total_price ?? 0);
@endphp
        <div class="details">
            <p><strong>Order number:</strong> {{ $order->order_number ?? ('#' . $order->id) }}</p>

            @if(count($items) > 0)
                <p style="margin-top: 16px; margin-bottom: 4px;"><strong>{{ count($items) === 1 ? 'Item' : 'Items' }}</strong></p>
                <ul class="item-list">
                    @foreach($items as $row)
                        @php
                            $title = $row['item']['title'] ?? $row['item']['name'] ?? 'Item';
                            $sub = $row['item']['subtitle'] ?? null;
                            $qty = max(1, (int) ($row['quantity'] ?? 1));
                        @endphp
                        <li>
                            <strong>{{ $title }}</strong>
                            @if(!empty($sub))
                                <br><span class="muted">{{ $sub }}</span>
                            @endif
                            @if($qty > 1)
                                <span class="muted"> — Qty {{ $qty }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p style="margin-top: 12px;"><strong>Item:</strong> Your Troosolar purchase</p>
            @endif

            <p style="margin-top: 16px;"><strong>Order total:</strong> ₦{{ number_format($orderTotal, 2) }}</p>
        </div>
