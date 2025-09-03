<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\Loan;
use App\Models\User;
use App\Models\Order;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
   public function index()
{
    try {
        // Summary counts
        $totalUsers = User::count();
        $totalLoans = Wallet::count();
        $totalOrders = Order::count();
        $totalLoanAmount = Wallet::sum('loan_balance');

        // Chart data per month (grouped counts)
        $chartData = collect(range(1, 12))->map(function ($month) {
            return [
                'month' => Carbon::create()->month($month)->format('M'),
                'approved_loan' => Wallet::whereMonth('created_at', $month)->where('status', 'approved')->count(),
                'pending_loan' => Wallet::whereMonth('created_at', $month)->where('status', 'pending')->count(),
                'overdue_loan' => Wallet::whereMonth('created_at', $month)->where('status', 'overdue')->count(),
                'orders' => Order::whereMonth('created_at', $month)->count(),
            ];
        });

        // Latest 5 Orders
        $latestOrders = Order::latest()->take(5)->get()->map(function ($order) {
            return [
                'customer' => optional($order->user)->first_name,
                'product' => optional($order->product)->name ?? 'Product',
                'total_price' => $order->total_price,
                'payment_method' => $order->payment_method,
                'payment_status' => $order->payment_status,
                'order_date' => $order->created_at->format('Y-m-d'),
            ];
        });

        // Latest 5 Users
        $latestUsers = User::latest()->take(5)->get()->map(function ($user) {
            return [
                'name' => $user->first_name . ' ' . $user->sur_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'created_at' => $user->created_at->format('d-m-Y h:iA'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'counts' => [
                    'total_users' => $totalUsers,
                    'total_loans' => $totalLoans,
                    'total_orders' => $totalOrders,
                    'loan_amount' => number_format($totalLoanAmount, 2),
                ],
                'chart' => $chartData,
                'latest_orders' => $latestOrders,
                'latest_users' => $latestUsers,
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Dashboard error: ' . $e->getMessage()
        ], 500);
    }
}
}