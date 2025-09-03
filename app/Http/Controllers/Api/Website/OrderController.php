<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Bundle;
use Illuminate\Support\Facades\Log;
use App\Helpers\ResponseHelper;
use App\Models\Bundles;
use App\Models\LoanApplication;
use App\Models\LoanCalculation;
use Illuminate\Support\Str;

class OrderController extends Controller
{
public function index()
{
    try {
        $userId = auth()->id();

        // Fetch all orders
        $orders = Order::with(['items'])
            ->where('user_id', $userId)
            ->latest()
            ->get();

        // Order summary
        $totalOrders = $orders->count();
        $pendingOrders = $orders->where('order_status', 'pending')->count();
        $completedOrders = $orders->where('order_status', 'delivered')->count();

        // Format orders and clean itemable_type
        $formattedOrders = $orders->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'order_status' => $order->order_status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'note' => $order->note,
                'total_price' => $order->total_price,
                'created_at' => $order->created_at->format('Y-m-d H:i:s'),
                'items' => $order->items->map(function ($item) {
                    return [
                        'itemable_type' => strtolower(class_basename($item->itemable_type)), // "product" or "bundle"
                        'itemable_id'   => $item->itemable_id,
                        'quantity'      => $item->quantity,
                        'unit_price'    => $item->unit_price,
                        'subtotal'      => $item->subtotal,
                    ];
                }),
            ];
        });

        return response()->json([
            'status' => true,
            'summary' => [
                'total_orders'     => $totalOrders,
                'pending_orders'   => $pendingOrders,
                'completed_orders' => $completedOrders,
            ],
            'orders' => $formattedOrders,
            'message' => 'Orders fetched successfully',
        ]);
    } catch (\Exception $e) {
        Log::error("Order Index Error: " . $e->getMessage());
        return ResponseHelper::error('Failed to fetch orders', 500);
    }
}



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

            foreach ($data['items'] as $item) {
                $model = $item['itemable_type'] === 'product'
                    ? Product::findOrFail($item['itemable_id'])
                    : Bundles::findOrFail($item['itemable_id']);

                $price = $model->discount_price ?? $model->price ?? $model->total_price;
                $subtotal = $price * $item['quantity'];

                OrderItem::create([
                    'order_id'      => $order->id,
                    'itemable_type' => $item['itemable_type'] === 'product' ? Product::class : Bundles::class,
                    'itemable_id'   => $item['itemable_id'],
                    'quantity'      => $item['quantity'],
                    'unit_price'    => $price,
                    'subtotal'      => $subtotal,
                ]);

                $total += $subtotal;
            }

            $order->update(['total_price' => $total]);
            $order->load(['items', 'deliveryAddress']);

            // Payment-specific logic
            if ($order->payment_method === 'direct') {
                $order->installation = [
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

                $order->loan_details = [
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

            return ResponseHelper::success($order,'Order placed successfully');
        } catch (\Exception $e) {
            Log::error("Order Store Error: " . $e->getMessage());
            return ResponseHelper::error('Failed to place order', 500);
        }
    }

    public function show($id)
    {
        try {
            $order = Order::with(['items', 'deliveryAddress'])
                ->where('user_id', auth()->id())
                ->findOrFail($id);

            if ($order->payment_method === 'direct') {
                $order->installation = [
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

                $order->loan_details = [
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

            return ResponseHelper::success($order,'Order fetched successfully');
        } catch (\Exception $e) {
            Log::error("Order Show Error: " . $e->getMessage());
            return ResponseHelper::error('Order not found', 404);
        }
    }

    public function destroy($id)
    {
        try {
            $order = Order::where('user_id', auth()->id())->findOrFail($id);
            $order->delete();

            return ResponseHelper::success('Order deleted successfully');
        } catch (\Exception $e) {
            Log::error("Order Delete Error: " . $e->getMessage());
            return ResponseHelper::error('Failed to delete order', 500);
        }
    }
}