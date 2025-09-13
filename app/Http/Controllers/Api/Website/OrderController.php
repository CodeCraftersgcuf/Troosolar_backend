<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Bundles;
use App\Models\LoanApplication;
use App\Models\LoanCalculation;
use App\Helpers\ResponseHelper;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * GET /api/orders
     * Returns orders for the authenticated user.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $isAdmin = $user ;

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
        try {
            $data = $request->validated();

            $order = Order::create([
                'user_id'             => auth()->id(),
                'delivery_address_id' => $data['delivery_address_id'] ?? null,
                'order_number'        => strtoupper(Str::random(10)),
                'payment_method'      => $data['payment_method'] ?? 'cash',
                'payment_status'      => 'paid',
                'order_status'        => 'pending',
                'note'                => $data['note'] ?? null,
                'total_price'         => 0,
            ]);

            $total = 0;
            $primaryProductId = null;
            $primaryBundleId  = null;

            foreach ($data['items'] as $item) {
                $isProduct = ($item['itemable_type'] === 'App\Models\Product');
                $model = $isProduct
                    ? Product::findOrFail($item['itemable_id'])
                    : Bundles::findOrFail($item['itemable_id']);

                $price    = $model->discount_price ?? $model->price ?? $model->total_price;
                $subtotal = $price * $item['quantity'];

                OrderItem::create([
                    'order_id'      => $order->id,
                    'itemable_type' => $isProduct ? Product::class : Bundles::class, // FQCN for morphTo
                    'itemable_id'   => $item['itemable_id'],
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $price,
                    'subtotal'      => $subtotal,
                ]);

                if ($isProduct && !$primaryProductId) {
                    $primaryProductId = $item['itemable_id'];
                } elseif (!$isProduct && !$primaryBundleId) {
                    $primaryBundleId = $item['itemable_id'];
                }

                $total += $subtotal;
            }

            // Save totals + primary product/bundle IDs
            $order->update([
                'total_price' => $total,
                'product_id'  => $primaryProductId,
                'bundle_id'   => $primaryBundleId,
            ]);

            // Reload for response
            $order->load(['items.itemable', 'deliveryAddress']);

            // Non-persisted extras for response
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

            $response = $this->formatOrder($order, $extras);

            return ResponseHelper::success($response, 'Order placed successfully');
        } catch (\Throwable $e) {
            Log::error("Order Store Error: {$e->getMessage()}");
            return ResponseHelper::error('Failed to place order', 500);
        }
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


}}