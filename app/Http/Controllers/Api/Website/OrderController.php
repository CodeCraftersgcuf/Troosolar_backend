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
use App\Models\DeliveryAddress;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Exception;

class OrderController extends Controller
{
    /**
     * GET /api/orders
     * Returns orders for the authenticated user.
     */
    public function updateStatus($orderId, Request $request){
        try {
            $order = Order::findOrFail($orderId);
            $order->order_status = $request->order_status;
            $order->save();
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
            $isAdmin = $user->role=='admin' ;

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

            $formatted = $orders->map(fn ($o) => $this->formatOrder($o, ['include_user_info' => $isAdmin]))->all();

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

        // 3) Create order shell
        $order = Order::create([
            'user_id'             => $userId,
            'delivery_address_id' => $deliveryAddressId,
            'order_number'        => strtoupper(Str::random(10)),
            'payment_method'      => $data['payment_method'] ?? 'cash',
            'payment_status'      => 'paid',
            'order_status'        => 'pending',
            'note'                => $data['note'] ?? null,
            'total_price'         => 0, // set later
        ]);

        // 4) Create order items from cart rows
        $total            = 0;
        $primaryProductId = null;
        $primaryBundleId  = null;

        foreach ($cartItems as $ci) {
            $itemable = $ci->itemable; // Product|Bundles|null
            if (! $itemable) {
                // skip broken cart rows
                continue;
            }

            $fqcn = $itemable instanceof Product ? Product::class : Bundles::class;

            // Prefer stored values; fallback to model price
            $unit = (int) $ci->unit_price;
            if ($unit <= 0) {
                $unit = (int) ($itemable->discount_price ?? $itemable->price ?? $itemable->total_price ?? 0);
            }
            $qty      = max(1, (int) $ci->quantity);
            $subtotal = (int) ($ci->subtotal ?? ($unit * $qty));

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

            $total += $subtotal;
        }

        // Edge: if every row was invalid
        if ($total <= 0) {
            throw ValidationException::withMessages([
                'cart' => ['Your cart items are invalid. Please re-add them.'],
            ]);
        }

        // 5) Persist totals + convenience ids
        $order->update([
            'total_price' => $total,
            'product_id'  => $primaryProductId,
            'bundle_id'   => $primaryBundleId,
        ]);

        // 6) Clear cart
        CartItem::where('user_id', $userId)->delete();

        // 7) Load for response
        $order->load(['items.itemable', 'deliveryAddress', 'user:id,first_name,sur_name,email,phone']);

        // 8) Optional extras like installation/loan (your prior logic can stay)
        $extras = [];
        if ($order->payment_method === 'direct') {
            $extras['installation'] = [
                'technician_name'   => 'John Doe',
                'installation_date' => now()->addDays(3)->toDateString(),
                'installation_fee'  => 2000,
            ];
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

        $response = $this->formatOrder($order, array_merge($extras, ['include_user_info' => true]));

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
            $user = auth()->user();
            $isAdmin = $user; // Based on your change: all authenticated users are treated as admin
            
            $query = Order::with(['items.itemable', 'deliveryAddress', 'user:id,first_name,sur_name,email,phone']);
            
            if (!$isAdmin) {
                // Regular users only see their own orders
                $query->where('user_id', $user->id);
            }
            // Admin users can see any order (no where clause needed)
            
            $order = $query->findOrFail($id);

            $extras = [];
            if ($order->payment_method === 'direct') {
                $extras['installation'] = [
                    'technician_name'   => 'John Doe',
                    'installation_date' => now()->addDays(3)->toDateString(),
                    'installation_fee'  => 2000,
                ];
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

            $response = $this->formatOrder($order, array_merge($extras, ['include_user_info' => $isAdmin]));

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
            'items'            => $order->items->map(fn ($i) => $this->formatOrderItem($i))->all(),
        ];

        // Add user information if this is an admin request
        if (isset($extras['include_user_info']) && $extras['include_user_info'] && $order->relationLoaded('user')) {
            $baseData['user_info'] = [
                'id' => $order->user->id,
                'name' => $order->user->first_name . ' ' . $order->user->sur_name,
                'email' => $order->user->email,
                'phone' => $order->user->phone,
            ];
        }

        return array_merge($baseData, $extras);
    }

    private function formatOrderItem(OrderItem $item): array
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

        return [
            'itemable_type' => strtolower(class_basename($item->itemable_type)), // "product" | "bundles"
            'itemable_id'   => $item->itemable_id,
            'quantity'      => $item->quantity,
            'unit_price'    => $item->unit_price,
            'subtotal'      => $item->subtotal,
            'item'          => $itemable ? [
                'id'             => $itemable->id,
                'title'          => $itemable->title ?? null,
                'featured_image' => $featured,
            ] : null,
        ];
    }
    public function paymentConfirmation(Request $request)
{
    $amount=$request->amount;
    $tx_id=$request->txId;
    $orderId=$request->orderId;
    $type=$request->type;
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
    $order->payment_status="paid";
    $order->update();
    $transaction=Transaction::create([
        'user_id'=>$order->user_id,
        'amount'=>$amount,
        'tx_id'=>$tx_id,
        "title"=>"Order Payment - Direct",
        "type"=>"outgoing",
        "method"=>"Direct",
        "status"=>"Completed",
        "transacted_at"=>now()

    ]);
    return ResponseHelper::success($transaction,"payment confirmed");


}

    /**
     * POST /api/orders/checkout
     * Buy Now checkout - Calculate invoice with optional fees
     */
    public function checkout(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'nullable|exists:products,id',
                'bundle_id' => 'nullable|exists:bundles,id',
                'amount' => 'nullable|numeric|min:0',
                'customer_type' => 'nullable|in:residential,sme,commercial',
                'product_category' => 'nullable|string',
                'installer_choice' => 'required|in:troosolar,own',
                'include_insurance' => 'nullable|boolean',
                'include_inspection' => 'nullable|boolean',
                'state_id' => 'nullable|exists:states,id',
                'delivery_location_id' => 'nullable|exists:delivery_locations,id',
                'add_ons' => 'nullable|array',
                'add_ons.*' => 'exists:add_ons,id',
            ]);

            $productPrice = 0;
            $product = null;
            $bundle = null;

            // Get product price from product_id, bundle_id, or amount
            $productId = isset($data['product_id']) && !empty($data['product_id']) ? $data['product_id'] : null;
            $bundleId = isset($data['bundle_id']) && !empty($data['bundle_id']) ? $data['bundle_id'] : null;
            $amount = isset($data['amount']) && !empty($data['amount']) ? (float) $data['amount'] : null;
            
            if ($productId) {
                $product = Product::findOrFail($productId);
                $productPrice = $product->discount_price ?? $product->price ?? 0;
            } elseif ($bundleId) {
                $bundle = Bundles::findOrFail($bundleId);
                $productPrice = $bundle->total_price ?? 0;
            } elseif ($amount) {
                // If amount is provided directly, use it
                $productPrice = $amount;
            } else {
                return ResponseHelper::error('Either product_id, bundle_id, or amount is required. Please provide one of them in your request.', 422);
            }

            // Get delivery and installation fees from state/delivery location or use defaults
            $deliveryFee = 25000; // Default
            $installationFee = 50000; // Default
            
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
            $inspectionFee = 0;
            $insuranceFee = 0;
            $addOnsTotal = 0;
            $addOns = [];

            // Installation fee (only if using Troosolar installer)
            if ($data['installer_choice'] === 'troosolar') {
                $materialCost = 30000; // Material cost (cables, breakers, etc.)
                
                // Inspection fee (optional for Buy Now)
                if ($data['include_inspection'] ?? false) {
                    $inspectionFee = 15000;
                }
            } else {
                // If using own installer, no installation fee or material cost
                $installationFee = 0;
            }

            // Insurance fee (optional for Buy Now, compulsory for BNPL)
            if ($data['include_insurance'] ?? false) {
                $insuranceFee = round($productPrice * 0.005, 2); // 0.5% of product price
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
                        $addOnPrice = round($productPrice * 0.005, 2);
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

            $invoice = [
                'product_price' => $productPrice,
                'installation_fee' => $installationFee,
                'material_cost' => $materialCost,
                'delivery_fee' => $deliveryFee,
                'inspection_fee' => $inspectionFee,
                'insurance_fee' => $insuranceFee,
                'add_ons_total' => $addOnsTotal,
                'add_ons' => $addOns,
                'total' => $total,
                'order_type' => 'buy_now',
                'installer_choice' => $data['installer_choice'],
                'note' => $data['installer_choice'] === 'troosolar' 
                    ? 'Installation fees may change after site inspection. Any difference will be updated and shared with you for a one-off payment before installation.'
                    : null,
            ];

            return ResponseHelper::success($invoice, 'Invoice calculated successfully');

        } catch (Exception $e) {
            Log::error('Checkout Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to calculate invoice: ' . $e->getMessage(), 500);
        }
    }
}