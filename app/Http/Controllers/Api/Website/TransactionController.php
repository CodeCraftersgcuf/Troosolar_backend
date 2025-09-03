<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    // Show all transactions for the logged-in user
 public function index()
{
    $userId = Auth::id();

    // Sync paid orders into transactions
    $orders = Order::where('user_id', $userId)
        ->where('payment_status', 'paid')
        ->get();

    foreach ($orders as $order) {
        Transaction::updateOrCreate(
            [
                'user_id'       => $userId,
                'title'         => 'Order #' . $order->order_number,
                'transacted_at' => $order->created_at,
            ],
            [
                'amount' => $order->total_price,
                'status' => $order->payment_status,
                'type'   => 'deposit',
                'method' => $order->payment_method ?? 'unknown',
            ]
        );
    }

    // Get transactions
    $transactions = Transaction::where('user_id', $userId)
        ->latest('transacted_at')
        ->get();

    // Totals without decimals
    $totalTransactions = $transactions->count();
    $totalDeposits = (int) $transactions->where('type', 'deposit')->sum('amount');
    $totalWithdrawals = (int) $transactions->where('type', 'withdrawal')->sum('amount');

    // Format transactions
    $formatted = $transactions->map(function ($transaction) {
        return [
            'id'             => $transaction->id,
            'payment_method' => $transaction->method,
            'price'          => (string) $transaction->amount,
            'date'           => $transaction->transacted_at->format('Y-m-d'),
            'time'           => $transaction->transacted_at->format('H:i:s'),
            'type'           => $transaction->type,
            'status'         => $transaction->status,
            'title'          => $transaction->title,
        ];
    });

    return response()->json([
        'status'       => true,
        'summary'      => [
            'total_transactions' => $totalTransactions,
            'total_deposits'     => $totalDeposits,
            'total_withdrawals'  => $totalWithdrawals,
        ],
        'transactions' => $formatted,
    ]);
}



    // Show a single transaction by ID
    public function show($id)
    {
        $userId = Auth::id();

        $transaction = Transaction::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$transaction) {
            return response()->json([
                'status'  => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'status'      => true,
            'transaction' => [
                'id'             => $transaction->id,
                'payment_method' => $transaction->method,
                'price'          => $transaction->amount,
                'date'           => $transaction->transacted_at->format('Y-m-d'),
                'time'           => $transaction->transacted_at->format('H:i:s'),
                'status'         => $transaction->status,
            ],
        ]);
    }

    public function singleTranscation()
    {
        $userId = Auth::id();
        $transcation = Transaction::where('user_id', $userId)->get();
        return ResponseHelper::success($transcation, 'get single transcation');
    }
}