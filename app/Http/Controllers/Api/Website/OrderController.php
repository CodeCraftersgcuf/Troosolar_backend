<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Wallet;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Bundles;
use App\Models\LoanApplication;
use App\Models\LoanCalculation;
use App\Helpers\ResponseHelper;
use App\Models\CartItem;
use App\Models\CheckoutSetting;
use App\Models\DeliveryAddress;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderPlacedConfirmationMail;
use App\Mail\OrderStatusUpdatedMail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Exception;
use App\Models\ReferralSettings;
use App\Models\AuditRequest;
use App\Services\ReferralRewardService;
use App\Support\CheckoutPricing;

class OrderController extends Controller
{
    private function resolveCatalogUnitPrice($itemable): float
    {
        if ($itemable instanceof Product) {
            $discount = (float) ($itemable->discount_price ?? 0);
            $basePrice = (float) ($itemable->price ?? 0);
            return $discount > 0 ? $discount : max(0, $basePrice);
        }

        if ($itemable instanceof Bundles) {
            $discount = (float) ($itemable->discount_price ?? 0);
            $basePrice = (float) ($itemable->total_price ?? 0);
            return $discount > 0 ? $discount : max(0, $basePrice);
        }

        return (float) ($itemable->price ?? $itemable->total_price ?? 0);
    }

    private function applyOutrightDiscount(float $amount, ?float $percentage): float
    {
        $pct = max(0, (float) ($percentage ?? 0));
        if ($pct <= 0 || $amount <= 0) {
            return $amount;
        }
        return max(0, round($amount - (($amount * $pct) / 100), 2));
    }

    /** Admin role can be stored as "admin", "Admin", etc. */
    private function isAuthenticatedAdmin(): bool
    {
        $user = Auth::user();
        if (! $user || ! isset($user->role)) {
            return false;
        }

        return in_array(strtolower((string) $user->role), ['admin', 'superadmin', 'super_admin'], true);
    }

    /**
     * One-line label for transactional emails (first meaningful line item or legacy product/bundle).
     */
    private function orderDeliveredSummaryLine(Order $order): string
    {
        $order->loadMissing(['items.itemable', 'product', 'bundle']);

        foreach ($order->items as $item) {
            $itemable = $item->itemable;
            if ($itemable) {
                $t = $itemable->title ?? $itemable->name ?? null;
                if ($t) {
                    return (string) $t;
                }
            }
        }

        if ($order->product) {
            return (string) ($order->product->title ?? 'Your product');
        }
        if ($order->bundle) {
            return (string) ($order->bundle->title ?? 'Your bundle');
        }

        return 'Your Troosolar purchase';
    }

    /** Customer-facing label for order_status values. */
    private function humanizeOrderStatus(?string $status): string
    {
        $k = strtolower(trim((string) ($status ?? '')));
        if ($k === '') {
            return '—';
        }

        return match ($k) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered', 'completed' => 'Delivered',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
            default => ucfirst($k),
        };
    }

    /**
     * Notify the customer by email whenever the order status actually changes.
     */
    private function notifyCustomerOrderStatusChange(Order $order, ?string $previousStatus): void
    {
        $new = strtolower(trim((string) ($order->order_status ?? '')));
        $prev = strtolower(trim((string) ($previousStatus ?? '')));
        if ($new === '' || $new === $prev) {
            return;
        }

        $order->loadMissing('user');
        $user = $order->user;
        if (! $user || ! $user->email) {
            return;
        }

        try {
            $summary = $this->orderDeliveredSummaryLine($order);
            $prevHuman = $this->humanizeOrderStatus($previousStatus);
            $newHuman = $this->humanizeOrderStatus($order->order_status);
            Mail::to($user->email)->send(new OrderStatusUpdatedMail($order, $user, $prevHuman, $newHuman, $summary));
        } catch (\Throwable $e) {
            Log::error('Order status update email failed: '.$e->getMessage(), [
                'order_id' => $order->id,
            ]);
        }
    }

    /**
     * Send confirmation email when a cart order is placed (POST /orders).
     */
    private function notifyCustomerOrderPlaced(Order $order): void
    {
        $order->loadMissing('user');
        $user = $order->user;
        if (! $user || ! $user->email) {
            return;
        }

        try {
            $order->loadMissing(['items.itemable', 'deliveryAddress']);
            $orderView = $this->formatOrder($order->fresh(['items.itemable', 'deliveryAddress']), []);
            Mail::to($user->email)->send(new OrderPlacedConfirmationMail($order, $user, $orderView));
        } catch (\Throwable $e) {
            Log::error('Order placed confirmation email failed: '.$e->getMessage(), [
                'order_id' => $order->id,
            ]);
        }
    }

    /**
     * GET /api/orders
     * Returns orders for the authenticated user.
     */
    public function updateStatus($orderId, Request $request){
        $request->validate([
            'order_status' => 'required|string|in:pending,processing,shipped,delivered,cancelled,refunded,completed',
        ]);

        try {
            $order = Order::findOrFail($orderId);
            $previousStatus = $order->order_status;
            $order->order_status = $request->order_status;
            $order->save();
            $this->notifyCustomerOrderStatusChange($order, $previousStatus);

            return ResponseHelper::success('Order status updated successfully', 200);
        } catch (\Throwable $e) {
            Log::error("Order Update Status Error: {$e->getMessage()}");
            return ResponseHelper::error('Failed to update order status', 500);
        }
    }
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $isAdmin = $this->isAuthenticatedAdmin();

            // Build query based on user role
            $query = Order::with(['items.itemable', 'deliveryAddress', 'user:id,first_name,sur_name,email,phone']);
            
            if (!$isAdmin) {
                // Regular users only see their own orders
                $query->where('user_id', $user->id);
            }
            // Admin users see all orders (no where clause needed)

            /** @var \Illuminate\Database\Eloquent\Collection $orders */
            $orders = $query->latest()->get();

            $summary = [
                'total_orders'     => $orders->count(),
                'pending_orders'   => $orders->where('order_status', 'pending')->count(),
                'completed_orders' => $orders->where('order_status', 'delivered')->count(),
                'user_type'        => $isAdmin ? 'admin' : 'user',
            ];

            $formatted = $orders->map(fn ($o) => $this->formatOrder($o, []))->all();

            return response()->json([
                'status'  => true,
                'summary' => $summary,
                'orders'  => $formatted,
                'message' => $isAdmin ? 'All orders fetched successfully for admin' : 'Orders fetched successfully',
            ]);
        } catch (\Throwable $e) {
            Log::error("Order Index Error: {$e->getMessage()}");
            return ResponseHelper::error('Failed to fetch orders', 500);
        }
    }

    /**
     * GET /api/orders/user/{userId}
     * Returns orders for a specific user id (admin/support usage).
     */
    public function forUser(int $userId)
    {
        try {
            // Add authorization here if needed (e.g., Gate/Policy)

            /** @var \Illuminate\Database\Eloquent\Collection $orders */
            $orders = Order::with(['items.itemable', 'deliveryAddress'])
                ->where('user_id', $userId)
                ->latest()
                ->get();

            $summary = [
                'total_orders'     => $orders->count(),
                'pending_orders'   => $orders->where('order_status', 'pending')->count(),
                'completed_orders' => $orders->where('order_status', 'delivered')->count(),
            ];

            $formatted = $orders->map(fn ($o) => $this->formatOrder($o))->all();

            return response()->json([
                'status'  => true,
                'summary' => $summary,
                'orders'  => $formatted,
                'message' => 'Orders fetched successfully for user '.$userId,
            ]);
        } catch (\Throwable $e) {
            Log::error("Order forUser Error: {$e->getMessage()}");
            return ResponseHelper::error('Failed to fetch user orders', 500);
        }
    }

    /**
     * POST /api/orders
     * Creates an order for the authenticated user.
     */
    public function store(StoreOrderRequest $request)
{
    $userId = auth()->id();
    $data   = $request->validated();

    return DB::transaction(function () use ($userId, $data) {
        // 1) Load cart
        $cartItems = CartItem::query()
            ->where('user_id', $userId)
            ->with('itemable') // Product|Bundles
            ->orderBy('id')
            ->get();

        if ($cartItems->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => ['Your cart is empty. Add items before placing an order.'],
            ]);
        }

        // 2) Optional: verify delivery address belongs to user
        $deliveryAddressId = $data['delivery_address_id'] ?? null;
        if ($deliveryAddressId) {
            $owned = DeliveryAddress::where('id', $deliveryAddressId)
                ->where('user_id', $userId)
                ->exists();
            if (! $owned) {
                throw ValidationException::withMessages([
                    'delivery_address_id' => ['Invalid delivery address.'],
                ]);
            }
        }

        $settings = CheckoutSetting::get();
        $deliveryFee = (float) $settings->delivery_fee;
        $installationFromProducts = (float) CheckoutPricing::installationTotalFromCartItems($cartItems);
        $installationAddon = (float) ($settings->installation_flat_addon ?? 0);
        $installationSumFull = $installationFromProducts + $installationAddon;
        $includeInstallation = (bool) ($data['include_installation'] ?? false);
        $insPct = (float) ($settings->insurance_fee_percentage ?? config('checkout.insurance_fee_percentage', 3));
        $vatPct = (float) ($settings->vat_percentage ?? config('checkout.vat_percentage', 7.5));
        $deliveryWindow = CheckoutPricing::deliveryWindow($settings);

        // 3) Create order shell
        $orderPayload = [
            'user_id' => $userId,
            'delivery_address_id' => $deliveryAddressId,
            'order_number' => strtoupper(Str::random(10)),
            'payment_method' => $data['payment_method'] ?? 'cash',
            'payment_status' => 'paid',
            'order_status' => 'pending',
            'note' => $data['note'] ?? null,
            'total_price' => 0,
        ];
        if (Schema::hasColumn('orders', 'estimated_delivery_from')) {
            $orderPayload['estimated_delivery_from'] = $deliveryWindow['estimated_from'];
            $orderPayload['estimated_delivery_to'] = $deliveryWindow['estimated_to'];
            $orderPayload['delivery_estimate_label'] = $deliveryWindow['label'];
        }
        if (Schema::hasColumn('orders', 'include_installation')) {
            $orderPayload['include_installation'] = $includeInstallation;
        }
        if (
            $includeInstallation
            && Schema::hasColumn('orders', 'installation_requested_date')
            && ! empty($data['installation_requested_date'] ?? null)
        ) {
            $orderPayload['installation_requested_date'] = $data['installation_requested_date'];
        }
        if (Schema::hasColumn('orders', 'order_type')) {
            $orderPayload['order_type'] = 'shop';
        }

        $order = Order::create($orderPayload);

        // 4) Create order items from cart rows
        $total            = 0;
        $primaryProductId = null;
        $primaryBundleId  = null;
        $orderPaymentMethod = strtolower((string) ($data['payment_method'] ?? ''));
        $isOutrightCheckout = in_array($orderPaymentMethod, ['direct', 'cash'], true);

        $referralCodeInput = trim((string) ($data['referral_code'] ?? ''));
        if ($referralCodeInput !== '') {
            $referrer = User::referrerForCheckoutCode($referralCodeInput, (int) $userId);
            if (! $referrer) {
                throw ValidationException::withMessages([
                    'referral_code' => ['This referral code is not valid.'],
                ]);
            }
        }

        // Must match CartController::checkoutSummary: direct/cash checkout uses admin outright % on line items.
        // (Referral code is validated separately when supplied; it does not gate the checkout discount.)
        $applyOutrightDiscount = $isOutrightCheckout;
        $referralSettings = $applyOutrightDiscount ? ReferralSettings::getSettings() : null;
        $outrightDiscountPercentage = $applyOutrightDiscount
            ? (float) ($referralSettings->outright_discount_percentage ?? 0)
            : 0.0;

        foreach ($cartItems as $ci) {
            $itemable = $ci->itemable; // Product|Bundles|null
            if (! $itemable) {
                // skip broken cart rows
                continue;
            }

            $fqcn = $itemable instanceof Product ? Product::class : Bundles::class;

            $catalogUnit = $this->resolveCatalogUnitPrice($itemable);
            $effectiveUnit = $isOutrightCheckout
                ? $this->applyOutrightDiscount($catalogUnit, $outrightDiscountPercentage)
                : $catalogUnit;
            $unit = (float) $effectiveUnit;
            $qty      = max(1, (int) $ci->quantity);
            $subtotal = (float) round($unit * $qty, 2);

            if ($itemable instanceof Product) {
                $availableStock = (int) ($itemable->stock ?? 0);
                if ($availableStock <= 0) {
                    throw ValidationException::withMessages([
                        'stock' => ["{$itemable->title} is out of stock."],
                    ]);
                }
                if ($qty > $availableStock) {
                    throw ValidationException::withMessages([
                        'stock' => ["Only {$availableStock} unit(s) available for {$itemable->title}."],
                    ]);
                }
            }

            OrderItem::create([
                'order_id'      => $order->id,
                'itemable_type' => $fqcn,
                'itemable_id'   => $ci->itemable_id,
                'quantity'      => $qty,
                'unit_price'    => $unit,
                'subtotal'      => $subtotal,
            ]);

            // track first product/bundle for convenience fields
            if ($fqcn === Product::class && ! $primaryProductId) {
                $primaryProductId = $ci->itemable_id;
            }
            if ($fqcn === Bundles::class && ! $primaryBundleId) {
                $primaryBundleId = $ci->itemable_id;
            }

            if ($itemable instanceof Product) {
                $itemable->decrement('stock', $qty);
            }

            $total += $subtotal;
        }

        // Edge: if every row was invalid
        if ($total <= 0) {
            throw ValidationException::withMessages([
                'cart' => ['Your cart items are invalid. Please re-add them.'],
            ]);
        }

        // 5) Persist totals (+ delivery / optional installation + insurance % + VAT)
        $itemsSubtotalOrder = (float) $total;
        $insuranceFee = $includeInstallation
            ? (float) CheckoutPricing::insuranceAmountFromPercent($itemsSubtotalOrder, $installationSumFull, $insPct)
            : 0.0;
        $taxableBase = $itemsSubtotalOrder + $deliveryFee;
        if ($includeInstallation) {
            $taxableBase += $installationSumFull + $insuranceFee;
        }
        $vatAmount = (float) CheckoutPricing::vatAmount((float) $taxableBase, $vatPct);
        $orderTotal = round($taxableBase + $vatAmount, 2);

        $updatePayload = [
            'total_price' => $orderTotal,
            'product_id' => $primaryProductId,
            'bundle_id' => $primaryBundleId,
            'delivery_fee' => $deliveryFee,
            'installation_price' => $includeInstallation ? $installationSumFull : 0.0,
            'insurance_fee' => $insuranceFee,
        ];
        if (Schema::hasColumn('orders', 'vat_amount')) {
            $updatePayload['vat_amount'] = $vatAmount;
        }
        $order->update($updatePayload);

        // 6) Clear cart
        CartItem::where('user_id', $userId)->delete();

        // Online (Flutterwave): payment already succeeded — persist transaction + referral rewards.
        if ($orderPaymentMethod === 'direct' && ! empty($data['flutterwave_transaction_id'] ?? null)) {
            $this->recordOrderPaymentTransactionAndReferral(
                $order->fresh(),
                (float) $orderTotal,
                (string) $data['flutterwave_transaction_id'],
                'direct'
            );
        }

        // 7) Load for response
        $order->load(['items.itemable', 'deliveryAddress', 'user:id,first_name,sur_name,email,phone']);

        // 8) Optional extras like installation/loan (your prior logic can stay)
        $extras = [];
        if ($order->payment_method === 'direct') {
            // No placeholder technician; optional date when column exists and is set
            if (Schema::hasColumn('orders', 'installation_requested_date') && $order->installation_requested_date) {
                $extras['installation'] = [
                    'installation_date' => $order->installation_requested_date instanceof \Carbon\CarbonInterface
                        ? $order->installation_requested_date->format('Y-m-d')
                        : (string) $order->installation_requested_date,
                ];
            }
        } elseif ($order->payment_method === 'loan') {
            $loan = LoanCalculation::where('user_id', $userId)->latest()->first();
            $application = LoanApplication::where('user_id', $userId)
                ->where('mono_loan_calculation', $loan?->id)
                ->latest()
                ->first();

            $installments = [];
            if ($loan?->monthly_payment && $loan?->repayment_date) {
                $installments[] = [
                    'installment_number' => 1,
                    'amount'             => $loan->monthly_payment,
                    'status'             => 'pending',
                    'due_date'           => $loan->repayment_date,
                ];
            }

            $extras['loan_details'] = [
                'loan_amount'         => $loan?->loan_amount,
                'product_amount'      => $loan?->product_amount,
                'repayment_duration'  => $loan?->repayment_duration,
                'monthly_payment'     => $loan?->monthly_payment,
                'interest_percentage' => $loan?->interest_percentage,
                'repayment_date'      => $loan?->repayment_date,
                'application'         => $application,
                'installments'        => $installments,
            ];
        }

        $response = $this->formatOrder($order, $extras);

        $this->notifyCustomerOrderPlaced($order);

        return ResponseHelper::success($response, 'Order placed successfully');
    });
}
    /**
     * GET /api/orders/{id}
     * Returns a single order for the authenticated user.
     */
    public function show($id)
    {
        try {
            $viewer = auth()->user();
            $isAdminViewer = $this->isAuthenticatedAdmin();

            $query = Order::with(['items.itemable', 'deliveryAddress', 'user:id,first_name,sur_name,email,phone']);
            
            if (! $isAdminViewer) {
                $query->where('user_id', $viewer->id);
            }
            
            $order = $query->findOrFail($id);

            $extras = [];
            // Buy Now: delivery row missing on relation but FK set (or legacy load)
            if (($order->order_type ?? null) === 'buy_now' && ! $order->deliveryAddress && $order->delivery_address_id) {
                $addr = DeliveryAddress::find($order->delivery_address_id);
                if ($addr) {
                    $extras['delivery_address'] = $addr;
                }
            }
            // BNPL orders: use application's property address when order has no delivery address
            if (($order->order_type ?? null) === 'bnpl' && !$order->deliveryAddress && $order->mono_calculation_id) {
                $bnplApplication = LoanApplication::where('mono_loan_calculation', $order->mono_calculation_id)
                    ->where('user_id', $order->user_id)
                    ->first();
                if ($bnplApplication && ($bnplApplication->property_address || $bnplApplication->property_state)) {
                    $extras['delivery_address'] = (object) [
                        'address' => $bnplApplication->property_address ?? '',
                        'state' => $bnplApplication->property_state ?? null,
                        'title' => 'BNPL delivery',
                        'phone_number' => $order->relationLoaded('user') && $order->user ? $order->user->phone : null,
                    ];
                }
            }
            if ($order->payment_method === 'direct') {
                if (Schema::hasColumn('orders', 'installation_requested_date') && $order->installation_requested_date) {
                    $extras['installation'] = [
                        'installation_date' => $order->installation_requested_date instanceof \Carbon\CarbonInterface
                            ? $order->installation_requested_date->format('Y-m-d')
                            : (string) $order->installation_requested_date,
                    ];
                }
            } elseif ($order->payment_method === 'loan') {
                $loan = LoanCalculation::where('user_id', auth()->id())->latest()->first();
                $application = LoanApplication::where('user_id', auth()->id())
                    ->where('mono_loan_calculation', $loan?->id)
                    ->latest()
                    ->first();

                $installments = [];
                if ($loan?->monthly_payment && $loan?->repayment_date) {
                    $installments[] = [
                        'installment_number' => 1,
                        'amount'             => $loan->monthly_payment,
                        'status'             => 'pending',
                        'due_date'           => $loan->repayment_date,
                    ];
                }

                $extras['loan_details'] = [
                    'loan_amount'         => $loan?->loan_amount,
                    'product_amount'      => $loan?->product_amount,
                    'repayment_duration'  => $loan?->repayment_duration,
                    'monthly_payment'     => $loan?->monthly_payment,
                    'interest_percentage' => $loan?->interest_percentage,
                    'repayment_date'      => $loan?->repayment_date,
                    'application'         => $application,
                    'installments'        => $installments,
                ];
            }

            if ($isAdminViewer && $viewer) {
                $extras['viewer_account'] = [
                    'id' => $viewer->id,
                    'first_name' => $viewer->first_name,
                    'sur_name' => $viewer->sur_name,
                    'email' => $viewer->email,
                ];
            }

            $response = $this->formatOrder($order, $extras);

            return ResponseHelper::success($response, 'Order fetched successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error("Order not found: {$e->getMessage()}");
            return ResponseHelper::error('Order not found', 404);
        } catch (\Exception $e) {
            Log::error("Order Show Error: {$e->getMessage()}");
            return ResponseHelper::error('Failed to fetch order details', 500);
        } catch (\Throwable $e) {
            Log::error("Critical Order Show Error: {$e->getMessage()}");
            return ResponseHelper::error('A critical error occurred while fetching order', 500);
        }
    }

    /**
     * DELETE /api/orders/{id}
     */
    public function destroy($id)
    {
        try {
            $order = Order::where('user_id', auth()->id())->findOrFail($id);
            $order->delete();

            return ResponseHelper::success('Order deleted successfully');
        } catch (\Throwable $e) {
            Log::error("Order Delete Error: {$e->getMessage()}");
            return ResponseHelper::error('Failed to delete order', 500);
        }
    }

    /* -------------------------- Helpers -------------------------- */

    private function formatOrder(Order $order, array $extras = []): array
    {
        $order->loadMissing(['items.itemable']);
        $items = $order->items->isNotEmpty()
            ? $order->items->map(fn ($i) => $this->formatOrderItem($i, $order))->all()
            : $this->buildSyntheticFormattedOrderItems($order);
        $totalPrice = (float) $order->total_price;
        // Amount-only checkout (no product_id / bundle_id) — still return one line for the dashboard
        if (count($items) === 0 && $totalPrice > 0) {
            $items = [[
                'itemable_type' => 'order',
                'itemable_id'   => null,
                'quantity'      => 1,
                'unit_price'    => (string) round($totalPrice, 2),
                'subtotal'      => (string) round($totalPrice, 2),
                'item'          => [
                    'id'               => null,
                    'title'            => 'Purchase',
                    'featured_image'   => null,
                ],
            ]];
        }
        $itemsSubtotalSum = array_sum(array_map(function ($i) {
            return (float) ($i['subtotal'] ?? 0);
        }, $items));

        // Receipt: pre-discount catalog subtotal vs charged (same idea as cart checkout-summary).
        $catalogItemsSubtotal = 0.0;
        $hasListPrices = false;
        foreach ($items as $i) {
            $qty = max(1, (int) ($i['quantity'] ?? 1));
            $sub = (float) ($i['subtotal'] ?? 0);
            $listUnit = isset($i['list_unit_price']) ? (float) $i['list_unit_price'] : 0.0;
            if ($listUnit > 0) {
                $hasListPrices = true;
                $catalogItemsSubtotal += round($listUnit * $qty, 2);
            } else {
                $catalogItemsSubtotal += $sub;
            }
        }
        $onlineCheckoutDiscount = 0.0;
        if ($hasListPrices) {
            $onlineCheckoutDiscount = max(0.0, round($catalogItemsSubtotal - $itemsSubtotalSum, 2));
        }

        $vatAmount = Schema::hasColumn('orders', 'vat_amount') ? (float) ($order->vat_amount ?? 0) : 0.0;
        $settingsForVat = CheckoutSetting::get();
        $vatPctDisplay = (float) ($settingsForVat->vat_percentage ?? config('checkout.vat_percentage', 7.5));

        $baseData = [
            'id'               => $order->id,
            'order_number'     => $order->order_number,
            'order_status'     => $order->order_status,
            'payment_status'   => $order->payment_status,
            'payment_method'   => $order->payment_method,
            'note'             => $order->note,
            'total_price'      => $order->total_price,
            'product_id'       => $order->product_id,
            'bundle_id'        => $order->bundle_id,
            'created_at'       => optional($order->created_at)->format('Y-m-d H:i:s'),
            'delivery_address' => $order->relationLoaded('deliveryAddress') ? $order->deliveryAddress : null,
            'items'            => $items,
            'items_subtotal'   => round($itemsSubtotalSum, 2),
            'catalog_items_subtotal' => $hasListPrices ? round($catalogItemsSubtotal, 2) : null,
            'online_checkout_discount_amount' => $onlineCheckoutDiscount > 0.005 ? round($onlineCheckoutDiscount, 2) : null,
            'delivery_fee'     => $order->delivery_fee,
            'insurance_fee'    => $order->insurance_fee,
            'installation_price' => $order->installation_price,
            'include_installation' => (bool) ($order->include_installation ?? false),
            'vat_amount'       => $vatAmount,
            'vat_percentage'   => $vatPctDisplay,
            'estimated_delivery_from' => optional($order->estimated_delivery_from)->format('Y-m-d'),
            'estimated_delivery_to' => optional($order->estimated_delivery_to)->format('Y-m-d'),
            'delivery_estimate_label' => $order->delivery_estimate_label,
        ];

        if (Schema::hasColumn('orders', 'installation_requested_date')) {
            $baseData['installation_requested_date'] = $order->installation_requested_date
                ? ($order->installation_requested_date instanceof \Carbon\CarbonInterface
                    ? $order->installation_requested_date->format('Y-m-d')
                    : (string) $order->installation_requested_date)
                : null;
        }

        // Order owner (always when user is loaded) — My Orders / order detail must not use the viewer's profile
        if ($order->relationLoaded('user') && $order->user) {
            $baseData['user_info'] = [
                'id' => $order->user->id,
                'name' => trim(($order->user->first_name ?? '').' '.($order->user->sur_name ?? '')),
                'first_name' => $order->user->first_name,
                'sur_name' => $order->user->sur_name,
                'email' => $order->user->email,
                'phone' => $order->user->phone,
            ];
        }

        return array_merge($baseData, $extras);
    }

    /**
     * Legacy Buy Now orders often have product_id/bundle_id on orders but no order_items rows.
     * Build one display line so GET /orders/{id} shows the real bundle/product name and matches order total.
     */
    private function buildSyntheticFormattedOrderItems(Order $order): array
    {
        $total = (float) ($order->total_price ?? 0);
        if ($total <= 0) {
            return [];
        }

        if ($order->bundle_id) {
            $bundle = Bundles::with('bundleItems.product')->find($order->bundle_id);
            if ($bundle) {
                $fake = new OrderItem([
                    'order_id'      => $order->id,
                    'itemable_type' => Bundles::class,
                    'itemable_id'   => $bundle->id,
                    'quantity'      => 1,
                    'unit_price'    => number_format($total, 2, '.', ''),
                    'subtotal'      => number_format($total, 2, '.', ''),
                ]);
                $fake->setRelation('itemable', $bundle);

                return [$this->formatOrderItem($fake, $order)];
            }
        }

        if ($order->product_id) {
            $product = Product::find($order->product_id);
            if ($product) {
                $fake = new OrderItem([
                    'order_id'      => $order->id,
                    'itemable_type' => Product::class,
                    'itemable_id'   => $product->id,
                    'quantity'      => 1,
                    'unit_price'    => number_format($total, 2, '.', ''),
                    'subtotal'      => number_format($total, 2, '.', ''),
                ]);
                $fake->setRelation('itemable', $product);

                return [$this->formatOrderItem($fake, $order)];
            }
        }

        return [];
    }

    private function formatOrderItem(OrderItem $item, ?Order $order = null): array
    {
        $itemable = $item->itemable; // Product | Bundles | null

        // Resolve image with fallback (bundle → first product’s image)
        $featured = null;
        if ($itemable) {
            $featured = $itemable->featured_image_url
                ?? ($itemable->featured_image ?? null);

            if (!$featured && $itemable instanceof Bundles) {
                $firstProduct = optional($itemable->bundleItems->first())->product;
                if ($firstProduct) {
                    $featured = $firstProduct->featured_image_url
                        ?? ($firstProduct->featured_image ?? null);
                }
            }
        }

        $title = $itemable ? ($itemable->title ?? $itemable->name ?? null) : null;
        $subtitle = null;
        if ($itemable instanceof Bundles) {
            $subtitle = $itemable->product_model ?? null;
            if ($subtitle && $title && trim((string) $subtitle) === trim((string) $title)) {
                $subtitle = null;
            }
        }

        $qty = max(1, (int) ($item->quantity ?? 1));
        $catalogUnit = $itemable ? $this->resolveCatalogUnitPrice($itemable) : 0.0;
        $unit = (float) ($item->unit_price ?? 0);
        $subtotal = (float) ($item->subtotal ?? 0);

        if ($unit <= 0 && $itemable) {
            $unit = $catalogUnit;
        }
        $unitRounded = round($unit, 2);
        if ($subtotal <= 0 && $unitRounded > 0) {
            $subtotal = round($unitRounded * $qty, 2);
        }

        $paymentMethod = $order ? strtolower((string) ($order->payment_method ?? '')) : '';
        $isOutrightCheckout = in_array($paymentMethod, ['direct', 'cash'], true);
        $showReferralList = $isOutrightCheckout
            && $itemable
            && $catalogUnit > 0
            && $catalogUnit > $unitRounded + 0.005;

        $row = [
            'itemable_type' => strtolower(class_basename($item->itemable_type)), // "product" | "bundles"
            'itemable_id'   => $item->itemable_id,
            'quantity'      => $item->quantity,
            'unit_price'    => $unitRounded,
            'subtotal'      => round($subtotal, 2),
            'item'          => $itemable ? [
                'id'             => $itemable->id,
                'title'          => $title,
                'subtitle'       => $subtitle,
                'featured_image' => $featured,
            ] : null,
        ];

        if ($showReferralList) {
            $row['list_unit_price'] = round($catalogUnit, 2);
            $derivedPct = $catalogUnit > 0
                ? round(100 * (1 - min($unitRounded, $catalogUnit) / $catalogUnit), 2)
                : 0.0;
            $row['referral_outright_discount_percent'] = $derivedPct;
        }

        return $row;
    }

    /**
     * Store a completed payment transaction and apply referral rewards (cart + legacy confirm flows).
     */
    private function recordOrderPaymentTransactionAndReferral(Order $order, float $amount, string $txId, string $type): Transaction
    {
        $title = match ($type) {
            'audit' => 'Audit Payment',
            'wallet' => 'Order Payment - Wallet',
            default => 'Order Payment - Direct',
        };

        $transaction = Transaction::create([
            'user_id' => $order->user_id,
            'amount' => $amount,
            'tx_id' => $txId,
            'title' => $title,
            'type' => 'outgoing',
            'method' => $type === 'wallet' ? 'Wallet' : 'Direct',
            'status' => 'Completed',
            'transacted_at' => now(),
        ]);

        $isBuyNowOrder = (($order->order_type ?? null) === 'buy_now');
        if ($isBuyNowOrder) {
            $rewardBase = Schema::hasColumn('orders', 'product_price')
                ? (float) ($order->product_price ?? $order->total_price ?? 0)
                : (float) ($order->total_price ?? 0);
            app(ReferralRewardService::class)->award(Auth::user(), $rewardBase, 'buy_now_completed', $order);
        }

        return $transaction;
    }

    public function paymentConfirmation(Request $request)
{
    try {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'orderId' => 'required|integer|exists:orders,id',
            'txId' => 'required|string',
            'type' => 'required|in:direct,audit,wallet',
            'installation_requested_date' => 'nullable|date|date_format:Y-m-d',
        ]);

        $amount = $request->amount;
        $tx_id = $request->txId;
        $orderId = $request->orderId;
        $type = $request->type;

    if($type=="wallet"){
        //check does user have that much loan
        $wallet=Wallet::where('user_id',Auth::user()->id)->first();
        if($amount < $wallet->loan_balance){
            //process the payment
            $wallet->loan_balance=$wallet->loan_balance-$amount;
            $wallet->save();
            $tx_id=date('ymdhis').rand(1000,9999);
        }else{
            return ResponseHelper::error("you don't have that much loan");
        }
    }

    $order=Order::where('id',$orderId)->first();
    if(!$order){
        return ResponseHelper::error("order does not found");
        }

        // Verify order belongs to authenticated user
        if($order->user_id != Auth::id()){
            return ResponseHelper::error("Unauthorized access to order", 403);
    }

    $order->payment_status="paid";
        if ($request->filled('installation_requested_date') && Schema::hasColumn('orders', 'installation_requested_date')) {
            $order->installation_requested_date = $request->installation_requested_date;
        }
    $order->update();

        $transaction = $this->recordOrderPaymentTransactionAndReferral(
            $order,
            (float) $amount,
            (string) $tx_id,
            $type
        );

        return ResponseHelper::success([
            'order_id' => $order->id,
            'payment_status' => 'confirmed',
            'transaction_id' => $tx_id,
            'amount' => (float)$amount,
            'type' => $type,
            'confirmed_at' => now()->toIso8601String(),
            'transaction' => $transaction
        ], "payment confirmed");

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (Exception $e) {
        Log::error('Payment Confirmation Error: ' . $e->getMessage());
        return ResponseHelper::error('Failed to confirm payment: ' . $e->getMessage(), 500);
    }
}

    /**
     * POST /api/orders/checkout
     * Buy Now checkout - Calculate invoice with optional fees
     */
    public function checkout(Request $request)
    {
        try {
            // Dynamic validation based on product_category
            $validationRules = [
                'product_id' => 'nullable|exists:products,id',
                'bundle_id' => 'nullable|exists:bundles,id',
                'amount' => 'nullable|numeric|min:0',
                'customer_type' => 'nullable|in:residential,sme,commercial',
                'product_category' => 'nullable|string',
                'include_insurance' => 'nullable|boolean',
                'include_inspection' => 'nullable|boolean',
                'state_id' => 'nullable|exists:states,id',
                'delivery_location_id' => 'nullable|exists:delivery_locations,id',
                'add_ons' => 'nullable|array',
                'add_ons.*' => 'exists:add_ons,id',
                'audit_type' => 'nullable|in:home-office,commercial',
                'audit_request_id' => 'nullable|exists:audit_requests,id',
                'property_state' => 'nullable|string',
                'property_address' => 'nullable|string',
                'property_floors' => 'nullable|integer',
                'property_rooms' => 'nullable|integer',
                'contact_name' => 'nullable|string|max:255',
                'contact_phone' => 'nullable|string|max:50',
            ];

            // Check if this is an audit order before validation
            $isAuditOrder = $request->has('product_category') && $request->product_category === 'audit';
            
            // installer_choice is required only for non-audit orders
            if (!$isAuditOrder) {
                $validationRules['installer_choice'] = 'nullable|in:troosolar,own';
            } else {
                $validationRules['installer_choice'] = 'nullable|in:troosolar,own';
            }

            $data = $request->validate($validationRules);
            if (!$isAuditOrder && empty($data['installer_choice'])) {
                $data['installer_choice'] = 'troosolar';
            }
            
            // For audit orders, skip installer_choice requirement
            if ($isAuditOrder) {
                // Link to audit request if provided
                $auditRequestId = $request->input('audit_request_id');
                $auditRequest = null;
                
                if ($auditRequestId) {
                    $auditRequest = \App\Models\AuditRequest::where('id', $auditRequestId)
                        ->where('user_id', Auth::id())
                        ->first();
                    if (!$auditRequest) {
                        return ResponseHelper::error('Invalid audit request ID', 422);
                    }
                }
                
                // Calculate audit fee based on property details
                $auditFee = $this->calculateAuditFee($auditRequest, $data);
                
                // Prepare audit order data - check if columns exist
                $auditOrderData = [
                    'user_id' => Auth::id(),
                    'total_price' => $auditFee,
                    'payment_status' => 'pending',
                    'order_status' => 'pending',
                    'payment_method' => 'direct',
                ];

                // Add optional columns only if they exist
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'order_type')) {
                    $auditOrderData['order_type'] = 'audit_only';
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'audit_request_id') && $auditRequestId) {
                    $auditOrderData['audit_request_id'] = $auditRequestId;
                }

                // Create audit order
                $order = Order::create($auditOrderData);
                
                // Link order to audit request
                if ($auditRequestId) {
                    \App\Models\AuditRequest::where('id', $auditRequestId)->update(['order_id' => $order->id]);
                }
                
                return ResponseHelper::success([
                    'order_id' => $order->id,
                    'audit_fee' => $auditFee,
                    'total' => $auditFee,
                    'order_type' => 'audit',
                    'audit_type' => $data['audit_type'] ?? ($auditRequest ? $auditRequest->audit_type : null),
                    'audit_request_id' => $auditRequestId,
                    'created_at' => $order->created_at->toIso8601String(),
                ], 'Audit order created successfully');
            }

            $productPrice = 0;
            $product = null;
            $bundle = null;

            // Get product price from product_id, bundle_id, or amount
            $productId = isset($data['product_id']) && !empty($data['product_id']) ? $data['product_id'] : null;
            $bundleId = isset($data['bundle_id']) && !empty($data['bundle_id']) ? $data['bundle_id'] : null;
            $amount = isset($data['amount']) && !empty($data['amount']) ? (float) $data['amount'] : null;
            
            if ($productId) {
                $product = Product::findOrFail($productId);
                $productDiscount = (float) ($product->discount_price ?? 0);
                $productPrice = $productDiscount > 0
                    ? $productDiscount
                    : (float) ($product->price ?? 0);
            } elseif ($bundleId) {
                $bundle = Bundles::with('bundleMaterials.material')->findOrFail($bundleId);
                $bundleDiscount = (float) ($bundle->discount_price ?? 0);
                $productPrice = $bundleDiscount > 0
                    ? $bundleDiscount
                    : (float) ($bundle->total_price ?? 0);
                // Use amount from request when bundle price is missing/zero (e.g. from bundle detail flow)
                if ($productPrice <= 0 && $amount !== null) {
                    $productPrice = (float) $amount;
                }
            } elseif ($amount !== null) {
                $productPrice = (float) $amount;
            } else {
                return ResponseHelper::error('Either product_id, bundle_id, or amount is required. Please provide one of them in your request.', 422);
            }

            $settings = ReferralSettings::getSettings();
            $outrightDiscountPercentage = (float) ($settings->outright_discount_percentage ?? 0);
            $outrightDiscountAmount = 0.0;
            if ($outrightDiscountPercentage > 0 && $productPrice > 0) {
                $outrightDiscountAmount = round(($productPrice * $outrightDiscountPercentage) / 100, 2);
                $productPrice = max(0, $productPrice - $outrightDiscountAmount);
            }

            // Get delivery and installation fees
            // For bundles, try to get from bundle materials first, then fallback to state/location
            $deliveryFee = 25000; // Default
            $installationFee = 50000; // Default
            $inspectionFeeFromBundle = 0;

            // If bundle, check for fees in bundle materials
            if ($bundle && $bundle->bundleMaterials) {
                foreach ($bundle->bundleMaterials as $bm) {
                    $materialName = $bm->material->name ?? '';
                    if (str_contains($materialName, 'Installation Fees')) {
                        $installationFee = (float) ($bm->material->selling_rate ?? $bm->material->rate ?? $installationFee);
                    } elseif (str_contains($materialName, 'Delivery Fees')) {
                        $deliveryFee = (float) ($bm->material->selling_rate ?? $bm->material->rate ?? $deliveryFee);
                    } elseif (str_contains($materialName, 'Inspection Fees')) {
                        $inspectionFeeFromBundle = (float) ($bm->material->selling_rate ?? $bm->material->rate ?? 0);
                    }
                }
            }
            
            // If not found in bundle, try state/delivery location
            if (isset($data['delivery_location_id']) && $data['delivery_location_id']) {
                $deliveryLocation = \App\Models\DeliveryLocation::find($data['delivery_location_id']);
                if ($deliveryLocation) {
                    $deliveryFee = $deliveryLocation->delivery_fee ?? $deliveryFee;
                    $installationFee = $deliveryLocation->installation_fee ?? $installationFee;
                }
            } elseif (isset($data['state_id']) && $data['state_id']) {
                $state = \App\Models\State::find($data['state_id']);
                if ($state) {
                    $deliveryFee = $state->default_delivery_fee ?? $deliveryFee;
                    $installationFee = $state->default_installation_fee ?? $installationFee;
                }
            }

            // Calculate fees
            $materialCost = 0;
            $inspectionFee = $inspectionFeeFromBundle; // Use from bundle if available
            $insuranceFee = 0;
            $addOnsTotal = 0;
            $addOns = [];

            // Installation fee (only if using Troosolar installer)
            $installerChoice = $data['installer_choice'] ?? null;
            if ($installerChoice === 'troosolar') {
                // For bundles, material cost is included in bundle price
                // For custom builds, calculate from materials
                if (!$bundle) {
                    $materialCost = 30000; // Material cost (cables, breakers, etc.)
                }
                
                // Inspection fee (optional for Buy Now, use bundle fee if available)
                if ($data['include_inspection'] ?? false && !$inspectionFeeFromBundle) {
                    $inspectionFee = 15000;
                }
            } else {
                // If using own installer, no installation fee
                $installationFee = 0;
                // But keep inspection fee if it was in bundle
                if (!$inspectionFeeFromBundle) {
                    $inspectionFee = 0;
                }
            }

            // Insurance fee (optional for Buy Now, compulsory for BNPL)
            if ($data['include_insurance'] ?? false) {
                $insuranceFee = round($productPrice * 0.03, 2); // 3% of product price
            }

            // Calculate add-ons total
            if (isset($data['add_ons']) && is_array($data['add_ons']) && count($data['add_ons']) > 0) {
                $addOnsList = \App\Models\AddOn::whereIn('id', $data['add_ons'])
                    ->where('is_active', true)
                    ->get();
                
                foreach ($addOnsList as $addOn) {
                    $addOnPrice = $addOn->price;
                    // If price is 0, it might be calculated (like insurance)
                    if ($addOnPrice == 0 && strtolower($addOn->title) == 'insurance') {
                        $addOnPrice = round($productPrice * 0.03, 2);
                    }
                    $addOnsTotal += $addOnPrice;
                    $addOns[] = [
                        'id' => $addOn->id,
                        'title' => $addOn->title,
                        'price' => $addOnPrice,
                        'quantity' => 1
                    ];
                }
            }

            $total = $productPrice + $installationFee + $materialCost + $deliveryFee + $inspectionFee + $insuranceFee + $addOnsTotal;

            // Calculate product breakdown (inverter, panels, batteries)
            $_bundleLineItems = null;
            $productBreakdown = $this->calculateProductBreakdown($product, $bundle, $productPrice, $_bundleLineItems);

            // Prepare order data - check if columns exist before including them
            $orderData = [
                'user_id' => Auth::id(),
                'product_id' => $productId,
                'bundle_id' => $bundleId,
                'total_price' => $total,
                'payment_status' => 'pending',
                'order_status' => 'pending',
                'payment_method' => 'direct',
            ];

            // Add optional columns only if they exist in the database
            $columnsToCheck = [
                'order_type' => 'buy_now',
                'product_price' => $productPrice,
                'installation_price' => $installationFee,
                'material_cost' => $materialCost,
                'delivery_fee' => $deliveryFee,
                'inspection_fee' => $inspectionFee,
                'insurance_fee' => $insuranceFee,
            ];

            foreach ($columnsToCheck as $column => $value) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', $column)) {
                    $orderData[$column] = $value;
                }
            }

            // Persist installation / delivery site from Buy Now flow (dashboard sends property_* + contact_*)
            $propAddr = trim((string) ($data['property_address'] ?? ''));
            $propState = trim((string) ($data['property_state'] ?? ''));
            $contactPhone = trim((string) ($data['contact_phone'] ?? ''));
            $contactName = trim((string) ($data['contact_name'] ?? ''));
            if ($propAddr !== '' || $propState !== '' || $contactPhone !== '' || $contactName !== '') {
                $user = Auth::user();
                $deliveryAddress = DeliveryAddress::create([
                    'user_id' => Auth::id(),
                    'phone_number' => $contactPhone !== '' ? $contactPhone : ($user->phone ?? ''),
                    'title' => $contactName !== '' ? $contactName : 'Installation site',
                    'address' => $propAddr !== '' ? $propAddr : ($propState !== '' ? $propState : ''),
                    'state' => $propState !== '' ? $propState : null,
                ]);
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'delivery_address_id')) {
                    $orderData['delivery_address_id'] = $deliveryAddress->id;
                }
            }

            // Create order record for Buy Now
            $order = Order::create($orderData);

            // Line items for My Orders / GET /orders/{id} (legacy orders had empty order_items)
            if ($productId && $product) {
                OrderItem::create([
                    'order_id'      => $order->id,
                    'itemable_type' => Product::class,
                    'itemable_id'   => $product->id,
                    'quantity'      => 1,
                    'unit_price'    => round($total, 2),
                    'subtotal'      => round($total, 2),
                ]);
            } elseif ($bundleId && $bundle) {
                OrderItem::create([
                    'order_id'      => $order->id,
                    'itemable_type' => Bundles::class,
                    'itemable_id'   => $bundle->id,
                    'quantity'      => 1,
                    'unit_price'    => round($total, 2),
                    'subtotal'      => round($total, 2),
                ]);
            }

            $invoice = [
                'order_id' => $order->id,
                'product_price' => $productPrice,
                'outright_discount_percentage' => $outrightDiscountPercentage,
                'outright_discount_amount' => $outrightDiscountAmount,
                'product_breakdown' => $productBreakdown,
                'installation_fee' => $installationFee,
                'material_cost' => $materialCost,
                'delivery_fee' => $deliveryFee,
                'inspection_fee' => $inspectionFee,
                'insurance_fee' => $insuranceFee,
                'add_ons_total' => $addOnsTotal,
                'add_ons' => $addOns,
                'total' => $total,
                'order_type' => 'buy_now',
                'installer_choice' => $installerChoice,
                'note' => ($installerChoice === 'troosolar') 
                    ? 'Installation fees may change after site inspection. Any difference will be updated and shared with you for a one-off payment before installation.'
                    : null,
            ];

            return ResponseHelper::success($invoice, 'Invoice calculated successfully');

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('Checkout Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to calculate invoice: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/orders/buy-now
     * Get all Buy Now orders (Admin only)
     */
    public function getBuyNowOrders(Request $request)
    {
        try {
            $query = Order::with(['items.itemable', 'deliveryAddress', 'bundle', 'product', 'user:id,first_name,sur_name,email,phone'])
                ->where('order_type', 'buy_now');

            // Filter by status
            if ($request->has('status')) {
                $query->where('order_status', $request->status);
            }

            // Search by user name or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('sur_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return ResponseHelper::success($orders, 'Buy Now orders retrieved successfully');
        } catch (Exception $e) {
            Log::error('Buy Now Orders Admin Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to retrieve Buy Now orders', 500);
        }
    }

    /**
     * GET /api/admin/orders/buy-now/{id}
     * Get single Buy Now order (Admin only)
     */
    public function getBuyNowOrder($id)
    {
        try {
            $order = Order::with(['items.itemable', 'deliveryAddress', 'bundle', 'product', 'user'])
                ->where('order_type', 'buy_now')
                ->findOrFail($id);

            return ResponseHelper::success($order, 'Buy Now order retrieved successfully');
        } catch (Exception $e) {
            Log::error('Buy Now Order Admin Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to retrieve Buy Now order', 500);
        }
    }

    /**
     * PUT /api/admin/orders/buy-now/{id}/status
     * Update Buy Now order status (Admin only)
     */
    public function updateBuyNowOrderStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'order_status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded,completed',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            $order = Order::where('order_type', 'buy_now')->findOrFail($id);
            $previousStatus = $order->order_status;
            $order->order_status = $request->order_status;

            // Only set admin_notes if column exists and value is provided
            if ($request->has('admin_notes') && Schema::hasColumn('orders', 'admin_notes')) {
                $order->admin_notes = $request->admin_notes;
            }

            $order->save();
            $this->notifyCustomerOrderStatusChange($order, $previousStatus);

            return ResponseHelper::success($order, 'Buy Now order status updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Buy Now Order Status Update Error: ' . $e->getMessage(), [
                'order_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return ResponseHelper::error('Failed to update Buy Now order status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/orders/bnpl
     * Get all BNPL orders (Admin only)
     */
    public function getBnplOrders(Request $request)
    {
        try {
            $query = Order::with([
                'items.itemable',
                'deliveryAddress',
                'user:id,first_name,sur_name,email,phone',
                'monoCalculation',
                'loanApplication:id,user_id,mono_loan_calculation,customer_type,product_category,property_state,property_address,property_landmark,property_floors,property_rooms,is_gated_estate,estate_name,estate_address,credit_check_method,social_media_handle,repayment_duration,loan_amount,order_items_snapshot,loan_plan_snapshot,created_at',
            ]);
            
            // BNPL orders: either have order_type='bnpl' OR have mono_calculation_id (for backward compatibility)
            // This handles cases where order_type column exists but might be NULL for older orders
            if (Schema::hasColumn('orders', 'order_type')) {
                $query->where(function($q) {
                    $q->where('order_type', 'bnpl')
                      ->orWhere(function($subQ) {
                          // Include orders with mono_calculation_id that don't have order_type set to buy_now or audit_only
                          $subQ->whereNotNull('mono_calculation_id')
                               ->where(function($typeQ) {
                                   $typeQ->whereNull('order_type')
                                         ->orWhereNotIn('order_type', ['buy_now', 'audit_only']);
                               });
                      });
                });
            } else {
                // Fallback: BNPL orders have mono_calculation_id
                $query->whereNotNull('mono_calculation_id');
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('order_status', $request->status);
            }

            // Search by user name or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('sur_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $orders = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return ResponseHelper::success($orders, 'BNPL orders retrieved successfully');
        } catch (Exception $e) {
            Log::error('BNPL Orders Admin Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return ResponseHelper::error('Failed to retrieve BNPL orders: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/admin/orders/bnpl/{id}
     * Get single BNPL order (Admin only)
     */
    public function getBnplOrder($id)
    {
        try {
            $query = Order::with([
                'items.itemable',
                'bundle',
                'product',
                'deliveryAddress',
                'user',
                'auditRequest',
                'monoCalculation.loanInstallments.transaction.user',
                'monoCalculation.loanRepayments.user',
                'loanApplication:id,user_id,mono_loan_calculation,customer_type,product_category,property_state,property_address,property_landmark,property_floors,property_rooms,is_gated_estate,estate_name,estate_address,credit_check_method,social_media_handle,repayment_duration,loan_amount,order_items_snapshot,loan_plan_snapshot,created_at',
            ]);
            
            // BNPL orders: either have order_type='bnpl' OR have mono_calculation_id
            if (Schema::hasColumn('orders', 'order_type')) {
                $query->where(function($q) {
                    $q->where('order_type', 'bnpl')
                      ->orWhere(function($subQ) {
                          $subQ->whereNotNull('mono_calculation_id')
                               ->where(function($typeQ) {
                                   $typeQ->whereNull('order_type')
                                         ->orWhereNotIn('order_type', ['buy_now', 'audit_only']);
                               });
                      });
                });
            } else {
                $query->whereNotNull('mono_calculation_id');
            }
            
            $order = $query->findOrFail($id);

            // Fallback for legacy BNPL orders where order_items table is empty:
            // derive order items from loan_application.order_items_snapshot.
            if ($order->items()->count() === 0 && $order->loanApplication && is_array($order->loanApplication->order_items_snapshot)) {
                $snapshot = $order->loanApplication->order_items_snapshot;

                $bundleIds = collect($snapshot)
                    ->where('itemable_type', Bundles::class)
                    ->pluck('itemable_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
                $productIds = collect($snapshot)
                    ->where('itemable_type', Product::class)
                    ->pluck('itemable_id')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $bundleMap = !empty($bundleIds)
                    ? Bundles::whereIn('id', $bundleIds)->get()->keyBy('id')
                    : collect();
                $productMap = !empty($productIds)
                    ? Product::whereIn('id', $productIds)->get()->keyBy('id')
                    : collect();

                $derivedItems = collect($snapshot)->map(function ($row, $idx) use ($bundleMap, $productMap) {
                    $itemableType = $row['itemable_type'] ?? null;
                    $itemableId = $row['itemable_id'] ?? null;
                    $qty = (int) ($row['quantity'] ?? 1);
                    $unitPrice = (float) ($row['unit_price'] ?? 0);
                    $subtotal = (float) ($row['subtotal'] ?? ($unitPrice * $qty));

                    $name = "Item " . ($idx + 1);
                    $type = null;

                    if ($itemableType === Bundles::class && $itemableId && $bundleMap->has($itemableId)) {
                        $bundle = $bundleMap->get($itemableId);
                        $name = $bundle->title ?? $bundle->name ?? $name;
                        $type = 'bundle';
                    } elseif ($itemableType === Product::class && $itemableId && $productMap->has($itemableId)) {
                        $product = $productMap->get($itemableId);
                        $name = $product->title ?? $product->name ?? $name;
                        $type = 'product';
                    }

                    return [
                        'id' => null,
                        'name' => $name,
                        'title' => $name,
                        'type' => $type,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'itemable_type' => $itemableType,
                        'itemable_id' => $itemableId,
                    ];
                })->values();

                $order->setRelation('items', $derivedItems);
            }

            $repaymentExtras = $this->buildAdminBnplRepaymentPayload($order);
            $payload = array_merge($order->toArray(), $repaymentExtras);
            if ($order->deliveryAddress) {
                $payload['delivery_address'] = $this->formatDeliveryAddressForApi($order->deliveryAddress, $order->user);
            }
            $payload = $this->mergeBnplLoanApplicationEstateFallback($order, $payload);

            return ResponseHelper::success($payload, 'BNPL order retrieved successfully');
        } catch (Exception $e) {
            Log::error('BNPL Order Admin Error: ' . $e->getMessage(), [
                'order_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return ResponseHelper::error('Failed to retrieve BNPL order: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Fill loan_application.estate_* from linked audit request when missing (legacy rows or duplicate loan_application).
     */
    private function mergeBnplLoanApplicationEstateFallback(Order $order, array $payload): array
    {
        if (empty($payload['loan_application']) || ! is_array($payload['loan_application'])) {
            return $payload;
        }
        $la = &$payload['loan_application'];
        $audit = $order->auditRequest;
        if (! $audit && $order->audit_request_id) {
            $audit = AuditRequest::query()->find($order->audit_request_id);
        }
        if (! $audit) {
            return $payload;
        }
        if (empty($la['estate_name']) && ! empty($audit->estate_name)) {
            $la['estate_name'] = $audit->estate_name;
        }
        if (empty($la['estate_address']) && ! empty($audit->estate_address)) {
            $la['estate_address'] = $audit->estate_address;
        }

        return $payload;
    }

    /**
     * Admin BNPL order detail: repayment schedule, summary, and history for tracking.
     */
    private function buildAdminBnplRepaymentPayload(Order $order): array
    {
        $mono = $order->monoCalculation;
        $orderUser = $order->user;
        $orderCustomerName = $orderUser
            ? trim((string) ($orderUser->first_name ?? '').' '.(string) ($orderUser->sur_name ?? ''))
            : null;

        if (! $mono) {
            return [
                'repayment_schedule' => [],
                'repayment_summary' => [
                    'total_installments' => 0,
                    'paid_installments' => 0,
                    'pending_installments' => 0,
                    'overdue_installments' => 0,
                    'total_amount' => 0.0,
                    'paid_amount' => 0.0,
                    'pending_amount' => 0.0,
                    'overdue_amount' => 0.0,
                    'order_customer_id' => $order->user_id,
                    'order_customer_name' => $orderCustomerName,
                    'order_customer_email' => $orderUser?->email,
                ],
                'repayment_history' => [],
                'loan_details' => null,
            ];
        }

        $mono->loadMissing(['loanInstallments.transaction.user', 'loanRepayments.user']);

        $sortedInstallments = $mono->loanInstallments->sortBy(function ($inst) {
            return $inst->payment_date ? $inst->payment_date->timestamp : 0;
        })->values();

        $installments = [];
        foreach ($sortedInstallments as $installment) {
            $paymentDate = $installment->payment_date;
            $paidAt = $installment->paid_at;
            $trans = $installment->transaction;
            $transactedAt = $trans ? $trans->transacted_at : null;
            $payer = $trans && $trans->relationLoaded('user') ? $trans->user : null;
            if ($trans && ! $payer && $trans->user_id) {
                $trans->loadMissing('user');
                $payer = $trans->user;
            }
            $payerName = $payer
                ? trim((string) ($payer->first_name ?? '').' '.(string) ($payer->sur_name ?? ''))
                : null;
            $paidByLabel = $installment->status === 'paid'
                ? ($payerName ?: $orderCustomerName ?: ($orderUser ? 'Customer #'.$orderUser->id : 'Customer'))
                : null;

            $isOverdue = $paymentDate && $paymentDate->lt(now()) && $installment->status !== 'paid';

            $installmentData = [
                'id' => $installment->id,
                'installment_number' => $installment->installment_number ?? null,
                'amount' => (float) $installment->amount,
                'payment_date' => $paymentDate ? $paymentDate->format('Y-m-d') : null,
                'status' => $installment->status,
                'paid_at' => $paidAt ? $paidAt->format('Y-m-d H:i:s') : null,
                'is_overdue' => $isOverdue,
                'computed_status' => $installment->computed_status,
                'paid_by_display' => $paidByLabel,
                'transaction' => $trans ? [
                    'id' => $trans->id,
                    'tx_id' => $trans->tx_id,
                    'method' => $trans->method,
                    'amount' => (float) $trans->amount,
                    'transacted_at' => $transactedAt ? $transactedAt->format('Y-m-d H:i:s') : null,
                    'user_id' => $trans->user_id,
                    'payer_name' => $payerName,
                    'payer_email' => $payer?->email,
                ] : null,
            ];
            $installments[] = $installmentData;
        }

        $totalInstallments = count($installments);
        $paidInstallments = count(array_filter($installments, fn ($i) => $i['status'] === 'paid'));
        $pendingInstallments = count(array_filter($installments, fn ($i) => $i['status'] !== 'paid'));
        $overdueInstallments = count(array_filter($installments, fn ($i) => $i['is_overdue'] === true));
        $totalAmount = array_sum(array_column($installments, 'amount'));
        $paidAmount = array_sum(array_column(array_filter($installments, fn ($i) => $i['status'] === 'paid'), 'amount'));
        $pendingAmount = $totalAmount - $paidAmount;
        $overdueAmount = array_sum(array_column(array_filter($installments, fn ($i) => $i['is_overdue'] === true), 'amount'));

        $repayments = [];
        $repaymentRows = $mono->loanRepayments->sortByDesc(function ($r) {
            return $r->created_at ? $r->created_at->timestamp : 0;
        })->values();
        foreach ($repaymentRows as $repayment) {
            $ru = $repayment->relationLoaded('user') ? $repayment->user : null;
            if (! $ru && $repayment->user_id) {
                $repayment->loadMissing('user');
                $ru = $repayment->user;
            }
            $repPayerName = $ru
                ? trim((string) ($ru->first_name ?? '').' '.(string) ($ru->sur_name ?? ''))
                : null;
            $repayments[] = [
                'id' => $repayment->id,
                'amount' => (float) $repayment->amount,
                'status' => $repayment->status,
                'created_at' => $repayment->created_at->format('Y-m-d H:i:s'),
                'user_id' => $repayment->user_id,
                'payer_name' => $repPayerName ?: ($orderCustomerName ?: null),
                'payer_email' => $ru?->email,
            ];
        }

        return [
            'repayment_schedule' => $installments,
            'repayment_summary' => [
                'total_installments' => $totalInstallments,
                'paid_installments' => $paidInstallments,
                'pending_installments' => $pendingInstallments,
                'overdue_installments' => $overdueInstallments,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
                'overdue_amount' => $overdueAmount,
                'order_customer_id' => $order->user_id,
                'order_customer_name' => $orderCustomerName,
                'order_customer_email' => $orderUser?->email,
            ],
            'repayment_history' => $repayments,
            'loan_details' => [
                'loan_amount' => (float) ($mono->loan_amount ?? 0),
                'down_payment' => (float) ($mono->down_payment ?? 0),
                'total_amount' => (float) ($mono->total_amount ?? 0),
                'repayment_duration' => $mono->repayment_duration,
                'interest_rate' => $mono->interest_rate,
            ],
        ];
    }

    /**
     * Resolve bundle/product for invoice & summary when order.bundle_id / product_id are empty
     * (common for BNPL: bundle stored on polymorphic order_items or loan_application.order_items_snapshot).
     *
     * @return array{0: ?Product, 1: ?Bundles}
     */
    private function resolveOrderBundleAndProductForInvoice(Order $order): array
    {
        $product = $order->product_id ? $order->product : null;
        $bundle = $order->bundle_id ? $order->bundle : null;

        if ($bundle) {
            $bundle->loadMissing(['bundleItems.product.category']);
        }
        if ($product) {
            $product->loadMissing('category');
        }
        if ($bundle || $product) {
            return [$product, $bundle];
        }

        $order->loadMissing(['items.itemable']);
        foreach ($order->items as $orderItem) {
            $itemable = $orderItem->itemable;
            if ($itemable instanceof Bundles) {
                $itemable->loadMissing(['bundleItems.product.category']);

                return [null, $itemable];
            }
            if ($itemable instanceof Product) {
                $itemable->loadMissing('category');

                return [$itemable, null];
            }
        }

        $order->loadMissing('loanApplication');
        $application = $order->loanApplication;
        if ($application && is_array($application->order_items_snapshot)) {
            foreach ($application->order_items_snapshot as $row) {
                $type = (string) ($row['itemable_type'] ?? '');
                $oid = $row['itemable_id'] ?? null;
                if ($oid === null || $type === '') {
                    continue;
                }
                if (class_exists($type) && is_a($type, Bundles::class, true)) {
                    $b = Bundles::with(['bundleItems.product.category'])->find((int) $oid);
                    if ($b) {
                        return [null, $b];
                    }
                }
                if (class_exists($type) && is_a($type, Product::class, true)) {
                    $p = Product::with('category')->find((int) $oid);
                    if ($p) {
                        return [$p, null];
                    }
                }
            }
        }

        return [null, null];
    }

    /**
     * API: show customer name as site contact when delivery row title is the BNPL placeholder.
     */
    private function formatDeliveryAddressForApi(?DeliveryAddress $address, ?User $user): ?array
    {
        if (! $address) {
            return null;
        }
        $data = $address->toArray();
        $customerName = $user ? trim((string) ($user->first_name ?? '').' '.(string) ($user->sur_name ?? '')) : '';
        $title = trim((string) ($data['title'] ?? ''));
        if ($customerName !== '' && ($title === '' || strcasecmp($title, 'BNPL delivery') === 0)) {
            $data['title'] = $customerName;
        }

        return $data;
    }

    /**
     * Classify a bundle catalog line using category + product title (categories are often missing or generic).
     */
    private function classifyInvoiceBundleLineType(string $categoryTitle, string $productTitle): string
    {
        $h = strtolower(trim($categoryTitle.' '.$productTitle));
        if ($h === '') {
            return 'other';
        }
        // Battery before inverter so titles like "battery module" are not classified as inverter
        if (str_contains($h, 'battery')
            || str_contains($h, 'lifepo')
            || str_contains($h, 'lipo')
            || str_contains($h, 'li-ion')
            || str_contains($h, 'lithium')) {
            return 'batteries';
        }
        if (str_contains($h, 'inverter')
            || str_contains($h, 'all-in-one')
            || str_contains($h, 'all in one')
            || str_contains($h, 'hybrid')) {
            return 'inverter';
        }
        if (preg_match('/\b(solar[-\s]?)?panel(s)?\b/', $h)
            || preg_match('/\b(pv|photovoltaic)\b/', $h)
            || str_contains($h, 'monofacial')
            || str_contains($h, 'bifacial')
            || str_contains($h, 'mono perc')
            || str_contains($h, 'polycrystalline')) {
            return 'panels';
        }

        return 'other';
    }

    /**
     * Calculate product breakdown (inverter, panels, batteries) and optional per-line invoice rows.
     *
     * @param  array<int, array{type: string, description: string, quantity: int, price: float}>|null  $bundleLineItemsOut
     */
    private function calculateProductBreakdown($product, $bundle, $totalPrice, ?array &$bundleLineItemsOut = null): array
    {
        if ($bundleLineItemsOut !== null) {
            $bundleLineItemsOut = [];
        }

        $breakdown = [
            'solar_inverter' => ['quantity' => 0, 'price' => 0, 'description' => ''],
            'solar_panels' => ['quantity' => 0, 'price' => 0, 'description' => ''],
            'batteries' => ['quantity' => 0, 'price' => 0, 'description' => ''],
        ];

        $totalPrice = (float) $totalPrice;
        $hasBuiltLineItems = false;

        if ($bundle) {
            $lines = [];

            try {
                $bundleItems = $bundle->bundleItems()->with('product.category')->get();

                foreach ($bundleItems as $item) {
                    if (! $item || ! $item->product) {
                        continue;
                    }
                    $category = $item->product->category;
                    $categoryName = $category ? strtolower((string) ($category->title ?? '')) : '';
                    $productTitle = (string) ($item->product->title ?? '');
                    $productDiscount = (float) ($item->product->discount_price ?? 0);
                    $baseUnit = $productDiscount > 0
                        ? $productDiscount
                        : (float) ($item->product->price ?? 0);
                    $rateOverride = (float) ($item->rate_override ?? 0);
                    $unitPrice = $rateOverride > 0 ? $rateOverride : $baseUnit;
                    $qty = max(1, (int) ($item->quantity ?? 1));
                    $lineTotal = round($unitPrice * $qty, 2);
                    $type = $this->classifyInvoiceBundleLineType($categoryName, $productTitle);

                    $lines[] = [
                        'type' => $type,
                        'description' => $productTitle !== '' ? $productTitle : 'Component',
                        'quantity' => $qty,
                        'catalog_total' => $lineTotal,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('Error processing bundle items: ' . $e->getMessage());
            }

            $lineCount = count($lines);

            if ($lineCount > 0 && $totalPrice > 0) {
                $catalogSum = round(array_sum(array_column($lines, 'catalog_total')), 2);

                if ($catalogSum > 0) {
                    foreach ($lines as $i => $ln) {
                        $lines[$i]['scaled_price'] = round($totalPrice * ($ln['catalog_total'] / $catalogSum), 2);
                    }
                } else {
                    $each = round($totalPrice / $lineCount, 2);
                    foreach ($lines as $i => $ln) {
                        $lines[$i]['scaled_price'] = $each;
                    }
                    $sumLines = round($each * $lineCount, 2);
                    $driftEq = round($totalPrice - $sumLines, 2);
                    if (abs($driftEq) >= 0.01) {
                        $lines[$lineCount - 1]['scaled_price'] = round($lines[$lineCount - 1]['scaled_price'] + $driftEq, 2);
                    }
                }

                $scaledSum = round(array_sum(array_column($lines, 'scaled_price')), 2);
                $drift = round($totalPrice - $scaledSum, 2);
                if (abs($drift) >= 0.01 && $lineCount > 0) {
                    $lines[0]['scaled_price'] = round($lines[0]['scaled_price'] + $drift, 2);
                }

                $bucketInv = 0.0;
                $bucketPan = 0.0;
                $bucketBat = 0.0;
                $qInv = $qPan = $qBat = 0;
                $dInv = $dPan = $dBat = '';

                foreach ($lines as $ln) {
                    $p = (float) $ln['scaled_price'];
                    $q = (int) $ln['quantity'];
                    $d = (string) $ln['description'];
                    switch ($ln['type']) {
                        case 'inverter':
                            $bucketInv += $p;
                            $qInv += $q;
                            $dInv = $dInv !== '' ? $dInv : $d;
                            break;
                        case 'panels':
                            $bucketPan += $p;
                            $qPan += $q;
                            $dPan = $dPan !== '' ? $dPan : $d;
                            break;
                        case 'batteries':
                            $bucketBat += $p;
                            $qBat += $q;
                            $dBat = $dBat !== '' ? $dBat : $d;
                            break;
                    }
                }

                $breakdown['solar_inverter'] = [
                    'quantity' => $bucketInv > 0 ? max(1, $qInv) : 0,
                    'price' => round($bucketInv, 2),
                    'description' => $bucketInv > 0 ? ($dInv !== '' ? $dInv : 'Solar Inverter') : '',
                ];
                $breakdown['solar_panels'] = [
                    'quantity' => $bucketPan > 0 ? max(1, $qPan) : 0,
                    'price' => round($bucketPan, 2),
                    'description' => $bucketPan > 0 ? ($dPan !== '' ? $dPan : 'Solar Panels') : '',
                ];
                $breakdown['batteries'] = [
                    'quantity' => $bucketBat > 0 ? max(1, $qBat) : 0,
                    'price' => round($bucketBat, 2),
                    'description' => $bucketBat > 0 ? ($dBat !== '' ? $dBat : 'Batteries') : '',
                ];

                if ($bundleLineItemsOut !== null) {
                    foreach ($lines as $ln) {
                        $bundleLineItemsOut[] = [
                            'type' => $ln['type'],
                            'description' => $ln['description'],
                            'quantity' => $ln['quantity'],
                            'price' => round((float) $ln['scaled_price'], 2),
                        ];
                    }
                    $hasBuiltLineItems = true;
                }
            } else {
                $breakdown['solar_inverter'] = [
                    'quantity' => 1,
                    'price' => round($totalPrice * 0.40, 2),
                    'description' => 'Solar Inverter',
                ];
                $breakdown['solar_panels'] = [
                    'quantity' => 1,
                    'price' => round($totalPrice * 0.35, 2),
                    'description' => 'Solar Panels',
                ];
                $breakdown['batteries'] = [
                    'quantity' => 1,
                    'price' => round($totalPrice * 0.25, 2),
                    'description' => 'Batteries',
                ];
            }
        } elseif ($product) {
            try {
                $category = $product->category;
                $categoryName = $category ? strtolower((string) ($category->title ?? '')) : '';
                $productTitle = (string) ($product->title ?? '');
                $lineType = $this->classifyInvoiceBundleLineType($categoryName, $productTitle);

                if ($lineType === 'inverter') {
                    $breakdown['solar_inverter'] = [
                        'quantity' => 1,
                        'price' => round($totalPrice, 2),
                        'description' => $product->title ?? 'Solar Inverter',
                    ];
                } elseif ($lineType === 'panels') {
                    $breakdown['solar_panels'] = [
                        'quantity' => 1,
                        'price' => round($totalPrice, 2),
                        'description' => $product->title ?? 'Solar Panels',
                    ];
                } elseif ($lineType === 'batteries') {
                    $breakdown['batteries'] = [
                        'quantity' => 1,
                        'price' => round($totalPrice, 2),
                        'description' => $product->title ?? 'Batteries',
                    ];
                } else {
                    $breakdown['solar_inverter'] = ['quantity' => 1, 'price' => round($totalPrice * 0.40, 2), 'description' => 'Solar Inverter'];
                    $breakdown['solar_panels'] = ['quantity' => 1, 'price' => round($totalPrice * 0.35, 2), 'description' => 'Solar Panels'];
                    $breakdown['batteries'] = ['quantity' => 1, 'price' => round($totalPrice * 0.25, 2), 'description' => 'Batteries'];
                }

                if ($bundleLineItemsOut !== null && $totalPrice > 0) {
                    $bundleLineItemsOut[] = [
                        'type' => $lineType,
                        'description' => $productTitle !== '' ? $productTitle : 'Product',
                        'quantity' => 1,
                        'price' => round($totalPrice, 2),
                    ];
                    $hasBuiltLineItems = true;
                }
            } catch (\Exception $e) {
                Log::warning('Error processing product: ' . $e->getMessage());
            }
        }

        // Legacy estimate only when we did not build real bundle lines and buckets are empty
        if (
            ! $hasBuiltLineItems
            && (float) $breakdown['solar_inverter']['price'] == 0
            && (float) $breakdown['solar_panels']['price'] == 0
            && (float) $breakdown['batteries']['price'] == 0
            && $totalPrice > 0
            && ! $bundle
            && ! $product
        ) {
            $breakdown['solar_inverter'] = ['quantity' => 1, 'price' => round($totalPrice * 0.40, 2), 'description' => 'Solar Inverter'];
            $breakdown['solar_panels'] = ['quantity' => 1, 'price' => round($totalPrice * 0.35, 2), 'description' => 'Solar Panels'];
            $breakdown['batteries'] = ['quantity' => 1, 'price' => round($totalPrice * 0.25, 2), 'description' => 'Batteries'];
        }

        return $breakdown;
    }

    /**
     * GET /api/orders/{id}/summary
     * Get order summary with item details, appliances, backup time
     */
    public function getOrderSummary($id)
    {
        try {
            $isAdmin = $this->isAuthenticatedAdmin();

            // Build query - admins can view any order, users can only view their own
            $query = Order::with([
                'product.category',
                'bundle.bundleItems.product.category',
                'user',
                'deliveryAddress',
                'items.itemable',
                'loanApplication',
            ])
                ->where('id', $id);

            if (!$isAdmin) {
                $query->where('user_id', Auth::id());
            }

            $order = $query->first();

            if (!$order) {
                Log::warning('Order Summary - Order not found', [
                    'order_id' => $id,
                    'user_id' => Auth::id(),
                    'is_admin' => $isAdmin
                ]);
                return ResponseHelper::error('Order not found', 404);
            }

            [$resolvedProduct, $resolvedBundle] = $this->resolveOrderBundleAndProductForInvoice($order);

            $items = [];
            $appliances = 'Standard household appliances';
            $backupTime = '8-12 hours (depending on usage)';

            try {
                if ($resolvedBundle) {
                    $bundle = $resolvedBundle;
                    $bundleItems = $bundle->bundleItems()->with('product.category')->get();
                    
                    foreach ($bundleItems as $item) {
                        if ($item->product) {
                            $productDetails = [];
                            try {
                                if (method_exists($item->product, 'details') && $item->product->details) {
                                    $productDetails = $item->product->details->pluck('detail')->toArray();
                                }
                            } catch (\Exception $e) {
                                Log::warning('Error getting product details: ' . $e->getMessage());
                            }

                            $items[] = [
                                'name' => $item->product->title ?? 'Unknown Product',
                                'description' => !empty($productDetails) ? implode(', ', $productDetails) : ($item->product->title ?? 'No description'),
                                'quantity' => $item->quantity ?? 1,
                        'price' => $this->resolveCatalogUnitPrice($item->product),
                            ];
                        }
                    }

                    // Calculate backup time based on bundle specs
                    if (isset($bundle->total_output) && $bundle->total_output) {
                        $backupTime = $this->calculateBackupTime($bundle->total_output, $bundle->total_load ?? 1000);
                    }
                } elseif ($resolvedProduct) {
                    $product = $resolvedProduct;
                    $productDetails = [];
                    try {
                        if (method_exists($product, 'details') && $product->details) {
                            $productDetails = $product->details->pluck('detail')->toArray();
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error getting product details: ' . $e->getMessage());
                    }

                    $items[] = [
                        'name' => $product->title ?? 'Unknown Product',
                        'description' => !empty($productDetails) ? implode(', ', $productDetails) : ($product->title ?? 'No description'),
                        'quantity' => 1,
                        'price' => $this->resolveCatalogUnitPrice($product),
                    ];
                } else {
                    // If no product or bundle, try to get items from order_items
                    try {
                        $orderItems = $order->items()->with('itemable')->get();
                        foreach ($orderItems as $orderItem) {
                            if ($orderItem->itemable) {
                                $itemName = $orderItem->itemable->title ?? 'Unknown Item';
                                $items[] = [
                                    'name' => $itemName,
                                    'description' => $itemName,
                                    'quantity' => $orderItem->quantity ?? 1,
                                    'price' => $orderItem->unit_price ?? 0,
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Error getting order items: ' . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error processing order items: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'trace' => $e->getTraceAsString()
                ]);
                // Continue with empty items array rather than failing completely
            }

            // Bundle linked but no component rows — still show the selected bundle as one line
            if ($resolvedBundle && count($items) === 0) {
                $bundle = $resolvedBundle;
                $pp = Schema::hasColumn('orders', 'product_price') ? (float) ($order->product_price ?? 0) : 0;
                if ($pp <= 0) {
                    $bundleDiscount = (float) ($bundle->discount_price ?? 0);
                    $basePrice = (float) ($bundle->total_price ?? 0);
                    $pp = $bundleDiscount > 0 ? $bundleDiscount : $basePrice;
                }
                if ($pp <= 0) {
                    $pp = (float) ($order->total_price ?? 0);
                }
                $desc = trim((string) ($bundle->product_model ?? ''));
                if ($desc === '') {
                    $desc = trim((string) ($bundle->what_is_inside_bundle_text ?? $bundle->detailed_description ?? ''));
                }
                $items[] = [
                    'name' => $bundle->title ?? 'Bundle',
                    'description' => $desc !== '' ? $desc : ($bundle->title ?? 'Bundle'),
                    'quantity' => 1,
                    'price' => round($pp, 2),
                ];
            }

            $installationRequested = null;
            if (Schema::hasColumn('orders', 'installation_requested_date') && $order->installation_requested_date) {
                $installationRequested = $order->installation_requested_date instanceof \Carbon\CarbonInterface
                    ? $order->installation_requested_date->format('Y-m-d')
                    : (string) $order->installation_requested_date;
            }

            return ResponseHelper::success([
                'order_id' => $order->id,
                'order_number' => $order->order_number ?? null,
                'items' => $items,
                'appliances' => $appliances,
                'backup_time' => $backupTime,
                'total_price' => $order->total_price ?? 0,
                'bundle_title' => $resolvedBundle?->title ?? $order->bundle?->title,
                'product_title' => $resolvedProduct?->title ?? $order->product?->title,
                'delivery_address' => $this->formatDeliveryAddressForApi($order->deliveryAddress, $order->user),
                'installation_requested_date' => $installationRequested,
            ], 'Order summary retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Order Summary - Model not found: ' . $e->getMessage());
            return ResponseHelper::error('Order not found', 404);
        } catch (\Exception $e) {
            Log::error('Order Summary Error: ' . $e->getMessage(), [
                'order_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return ResponseHelper::error('Failed to retrieve order summary: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Calculate audit fee based on property details
     */
    private function calculateAuditFee($auditRequest, $data)
    {
        // Base audit fee
        $baseFee = 50000; // ₦50,000 base fee
        
        // If amount is explicitly provided, use it
        if (isset($data['amount']) && $data['amount'] > 0) {
            return (float) $data['amount'];
        }
        
        // Get property details from audit request or data
        $floors = $auditRequest ? $auditRequest->property_floors : ($data['property_floors'] ?? 1);
        $rooms = $auditRequest ? $auditRequest->property_rooms : ($data['property_rooms'] ?? 1);
        $auditType = $auditRequest ? $auditRequest->audit_type : ($data['audit_type'] ?? 'home-office');
        
        // Calculate fee based on property size
        // Base fee + (floors * 5000) + (rooms * 2000)
        $sizeFee = ($floors * 5000) + ($rooms * 2000);
        
        // Commercial audits cost more
        if ($auditType === 'commercial') {
            $baseFee = 100000; // ₦100,000 base for commercial
            $sizeFee = ($floors * 10000) + ($rooms * 5000); // Higher multiplier for commercial
        }
        
        $totalFee = $baseFee + $sizeFee;
        
        // Cap at reasonable maximum
        $maxFee = $auditType === 'commercial' ? 500000 : 200000;
        
        return min($totalFee, $maxFee);
    }

    /**
     * Calculate backup time based on system output and load
     */
    private function calculateBackupTime($output, $load)
    {
        if ($load <= 0) $load = 1000; // Default load
        
        // Simple calculation: hours = (battery_capacity * efficiency) / load
        // Assuming battery capacity is roughly 70% of output
        $batteryCapacity = $output * 0.7;
        $efficiency = 0.85; // 85% efficiency
        $hours = ($batteryCapacity * $efficiency) / $load;
        
        if ($hours >= 12) {
            return '12+ hours';
        } elseif ($hours >= 8) {
            return '8-12 hours';
        } elseif ($hours >= 6) {
            return '6-8 hours';
        } else {
            return '4-6 hours';
        }
    }

    /**
     * GET /api/orders/{id}/invoice-details
     * Get detailed invoice breakdown
     */
    public function getInvoiceDetails($id)
    {
        try {
            $query = Order::with([
                'product.category',
                'bundle.bundleItems.product.category',
                'deliveryAddress',
                'user',
                'items.itemable',
                'loanApplication',
            ])
                ->where('id', $id);

            if (! $this->isAuthenticatedAdmin()) {
                $query->where('user_id', Auth::id());
            }

            $order = $query->first();

            if (!$order) {
                return ResponseHelper::error('Order not found', 404);
            }

            [$product, $bundle] = $this->resolveOrderBundleAndProductForInvoice($order);
            
            // Calculate total price for breakdown
            $totalPrice = 0;
            if (Schema::hasColumn('orders', 'product_price') && $order->product_price) {
                $totalPrice = $order->product_price;
            } else {
                $totalPrice = $order->total_price ?? 0;
            }

            $bundleLineItems = [];
            $productBreakdown = $this->calculateProductBreakdown(
                $product,
                $bundle,
                (float) $totalPrice,
                $bundleLineItems
            );

            $installationRequested = null;
            if (Schema::hasColumn('orders', 'installation_requested_date') && $order->installation_requested_date) {
                $installationRequested = $order->installation_requested_date instanceof \Carbon\CarbonInterface
                    ? $order->installation_requested_date->format('Y-m-d')
                    : (string) $order->installation_requested_date;
            }

            return ResponseHelper::success([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'bundle_title' => $bundle?->title ?? $order->bundle?->title,
                'product_title' => $product?->title ?? $order->product?->title,
                'delivery_address' => $this->formatDeliveryAddressForApi($order->deliveryAddress, $order->user),
                'installation_requested_date' => $installationRequested,
                'invoice' => [
                    'solar_inverter' => $productBreakdown['solar_inverter'],
                    'solar_panels' => $productBreakdown['solar_panels'],
                    'batteries' => $productBreakdown['batteries'],
                    'bundle_line_items' => $bundleLineItems,
                    'material_cost' => (Schema::hasColumn('orders', 'material_cost') ? ($order->material_cost ?? 0) : 0),
                    'installation_fee' => $order->installation_price ?? 0,
                    'delivery_fee' => (Schema::hasColumn('orders', 'delivery_fee') ? ($order->delivery_fee ?? 0) : 0),
                    'inspection_fee' => (Schema::hasColumn('orders', 'inspection_fee') ? ($order->inspection_fee ?? 0) : 0),
                    'insurance_fee' => (Schema::hasColumn('orders', 'insurance_fee') ? ($order->insurance_fee ?? 0) : 0),
                    'subtotal' => $totalPrice,
                    'total' => $order->total_price ?? 0,
                ],
            ], 'Invoice details retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Invoice Details Error - Order not found: ' . $e->getMessage());
            return ResponseHelper::error('Order not found', 404);
        } catch (\Exception $e) {
            Log::error('Invoice Details Error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return ResponseHelper::error('Failed to retrieve invoice details: ' . $e->getMessage(), 500);
        }
    }
}