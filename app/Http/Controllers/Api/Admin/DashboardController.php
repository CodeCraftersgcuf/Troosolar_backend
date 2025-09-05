<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\Bundles;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        try {
            // Summary counts
            $totalUsers      = User::count();
            $totalLoans      = Wallet::count();
            $totalOrders     = Order::count();
            $totalLoanAmount = Wallet::sum('loan_balance');

            // Chart data per month (grouped counts)
            $chartData = collect(range(1, 12))->map(function ($month) {
                return [
                    'month'         => Carbon::create()->month($month)->format('M'),
                    'approved_loan' => Wallet::whereMonth('created_at', $month)->where('status', 'approved')->count(),
                    'pending_loan'  => Wallet::whereMonth('created_at', $month)->where('status', 'pending')->count(),
                    'overdue_loan'  => Wallet::whereMonth('created_at', $month)->where('status', 'overdue')->count(),
                    'orders'        => Order::whereMonth('created_at', $month)->count(),
                ];
            });

            // Latest 5 Orders (with user + itemable for image fallback)
            $latestOrders = Order::with([
                    'user:id,first_name,sur_name',
                    'product:id,featured_image',
                    'items.itemable', // relies on OrderItem::morphWith() to pull bundleItems.product
                ])
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($order) {
                    return [
                        'order_id'       => $order->id,                 // ✅ order id
                        'user_id'        => $order->user_id,            // ✅ user id
                        'customer'       => trim(($order->user->first_name ?? '') . ' ' . ($order->user->sur_name ?? '')) ?: null,
                        // if you saved product_id on orders
                        'product_image'  => $this->resolveOrderCoverImage($order), // ✅ image (product/bundle fallback)
                        'total_price'    => $order->total_price,
                        'payment_method' => $order->payment_method,
                        'payment_status' => $order->payment_status,
                        'order_date'     => optional($order->created_at)->format('Y-m-d'),
                    ];
                });

            // Latest 5 Users
            $latestUsers = User::latest()
                ->take(5)
                ->get()
                ->map(function ($user) {
                    return [
                        'user_id'    => $user->id, // include id too if useful
                        'name'       => trim(($user->first_name ?? '') . ' ' . ($user->sur_name ?? '')) ?: ($user->first_name ?? null),
                        'email'      => $user->email,
                        'phone'      => $user->phone,
                        'created_at' => optional($user->created_at)->format('d-m-Y h:iA'),
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data'   => [
                    'counts' => [
                        'total_users'  => $totalUsers,
                        'total_loans'  => $totalLoans,
                        'total_orders' => $totalOrders,
                        'loan_amount'  => number_format($totalLoanAmount, 2),
                    ],
                    'chart'         => $chartData,
                    'latest_orders' => $latestOrders,
                    'latest_users'  => $latestUsers,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Dashboard error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Decide which image to show for an order:
     * 1) Product image if order has product_id or a product line-item
     * 2) Bundle image if it's a bundle line-item
     * 3) If bundle image missing, fallback to first product's image inside the bundle
     */
    private function resolveOrderCoverImage(Order $order): ?string
    {
        // 1) If you saved product_id on orders and eager-loaded 'product'
        if ($order->relationLoaded('product') && $order->product) {
            $img = $order->product->featured_image_url ?? $order->product->featured_image ?? null;
            if ($img) return $img;
        }

        // 2) Walk through items for product first
        foreach ($order->items as $item) {
            $itemable = $item->itemable;

            // Product line-item
            if ($itemable instanceof Product) {
                $img = $itemable->featured_image_url ?? $itemable->featured_image ?? null;
                if ($img) return $img;
            }
        }

        // 3) Then check bundle line-items (bundle image or fallback to its first product)
        foreach ($order->items as $item) {
            $itemable = $item->itemable;

            if ($itemable instanceof Bundles) {
                // Bundle image first
                $img = $itemable->featured_image_url ?? $itemable->featured_image ?? null;
                if ($img) return $img;

                // Fallback to first product inside the bundle
                $firstProduct = optional($itemable->bundleItems->first())->product;
                if ($firstProduct) {
                    $img = $firstProduct->featured_image_url ?? $firstProduct->featured_image ?? null;
                    if ($img) return $img;
                }
            }
        }

        return null;
    }
}