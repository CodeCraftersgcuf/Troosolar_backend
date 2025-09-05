<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * GET /api/transactions
     * All transactions for the authenticated user.
     */
    public function index()
    {
        try {
            $userId = Auth::id();

            // Sync paid orders into transactions (kept from your code)
            $this->syncPaidOrdersIntoTransactions($userId);

            [$summary, $formatted] = $this->fetchAndFormat($userId);

            return response()->json([
                'status'       => true,
                'summary'      => $summary,
                'transactions' => $formatted,
            ]);
        } catch (\Throwable $e) {
            Log::error('Transactions index error: '.$e->getMessage());
            return ResponseHelper::error('Failed to fetch transactions', 500);
        }
    }

    /**
     * GET /api/transactions/user/{userId}
     * Transactions for a specific user id (admin/support usage).
     */
    public function forUser(int $userId)
    {
        try {
            // TODO: add authorization (policy/gate/role) if only admins should use this

            $this->syncPaidOrdersIntoTransactions($userId);

            [$summary, $formatted] = $this->fetchAndFormat($userId);

            return response()->json([
                'status'       => true,
                'summary'      => $summary,
                'transactions' => $formatted,
                'message'      => 'Transactions fetched successfully for user '.$userId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Transactions forUser error: '.$e->getMessage());
            return ResponseHelper::error('Failed to fetch user transactions', 500);
        }
    }

    /**
     * GET /api/transactions/{id}
     * Single transaction for the authenticated user.
     */
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
                'type'           => $transaction->type,
                'title'          => $transaction->title,
            ],
        ]);
    }

    /**
     * GET /api/single-trancastion
     * (kept exactly as you had it)
     */
    public function singleTranscation()
    {
        $userId = Auth::id();
        $transcation = Transaction::where('user_id', $userId)->get();
        return ResponseHelper::success($transcation, 'get single transcation');
    }

    /* -------------------------- Helpers -------------------------- */

    /**
     * Sync "paid" orders into the transactions table for a user.
     * Mirrors your original updateOrCreate behavior.
     */
    private function syncPaidOrdersIntoTransactions(int $userId): void
    {
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
    }

    /**
     * Pull + format transactions for a user.
     *
     * @return array [$summary, $formatted]
     */
    private function fetchAndFormat(int $userId): array
    {
        $transactions = Transaction::where('user_id', $userId)
            ->latest('transacted_at')
            ->get();

        $totalTransactions = $transactions->count();
        $totalDeposits     = (int) $transactions->where('type', 'deposit')->sum('amount');
        $totalWithdrawals  = (int) $transactions->where('type', 'withdrawal')->sum('amount');

        $summary = [
            'total_transactions' => $totalTransactions,
            'total_deposits'     => $totalDeposits,
            'total_withdrawals'  => $totalWithdrawals,
        ];

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

        return [$summary, $formatted];
    }
}