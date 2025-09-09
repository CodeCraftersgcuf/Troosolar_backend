<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    // GET /api/transactions   and GET /api/admin/users
    public function index(Request $request)
    {
        try {
            $auth    = Auth::user();
            $isAdmin = $auth && strcasecmp((string)$auth->role, 'Admin') === 0;

            if ($isAdmin && !$request->filled('user_id')) {
                $this->syncPaidOrdersIntoTransactionsForAll();
                [$summary, $rows] = $this->fetchAndFormatAll(
                    type:   $request->query('type'),
                    status: $request->query('status'),
                    q:      $request->query('q')
                );
            } else {
                $userId = $request->filled('user_id') && $isAdmin
                    ? (int)$request->query('user_id')
                    : (int)($auth?->id ?? 0);

                if ($userId > 0) {
                    $this->syncPaidOrdersIntoTransactions($userId);
                }

                [$summary, $rows] = $this->fetchAndFormatForUser(
                    userId: $userId,
                    type:   $request->query('type'),
                    status: $request->query('status'),
                    q:      $request->query('q')
                );
            }

            return response()->json([
                'status'       => true,
                'summary'      => $summary,
                'transactions' => $rows,
            ]);
        } catch (\Throwable $e) {
            Log::error('Transactions index error: '.$e->getMessage());
            return ResponseHelper::error('Failed to fetch transactions', 500);
        }
    }

    // GET /api/transactions/user/{userId}
    public function forUser(int $userId, Request $request)
    {
        try {
            $this->syncPaidOrdersIntoTransactions($userId);

            [$summary, $rows] = $this->fetchAndFormatForUser(
                userId: $userId,
                type:   $request->query('type'),
                status: $request->query('status'),
                q:      $request->query('q')
            );

            return response()->json([
                'status'       => true,
                'summary'      => $summary,
                'transactions' => $rows,
                'message'      => 'Transactions fetched successfully for user '.$userId,
            ]);
        } catch (\Throwable $e) {
            Log::error('Transactions forUser error: '.$e->getMessage());
            return ResponseHelper::error('Failed to fetch user transactions', 500);
        }
    }

    // GET /api/transactions/{id}
    public function show($id)
    {
        $userId = Auth::id();

        $t = Transaction::with('user:id,first_name,sur_name,email,phone,profile_picture,user_code') // <— removed name
            ->where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$t) {
            return response()->json(['status' => false, 'message' => 'Transaction not found'], 404);
        }

        [$name, $email, $phone] = $this->extractUserBasics($t);

        return response()->json([
            'status'      => true,
            'transaction' => [
                'id'             => $t->id,
                'name'           => $name,
                'payment_method' => $t->method,
                'price'          => (string)$t->amount,
                'date'           => optional($t->transacted_at)->format('Y-m-d'),
                'time'           => optional($t->transacted_at)->format('H:i:s'),
                'type'           => $t->type,
                'tx_id'          => $t->tx_id ?? $t->reference ?? (string)$t->id,
                'status'         => $t->status,
                'title'          => $t->title,
                'email'          => $email,
                'phone'          => $phone,
            ],
        ]);
    }

    // GET /api/single-trancastion
    public function singleTranscation()
    {
        $userId = Auth::id();
        $txs = Transaction::where('user_id', $userId)->get();
        return ResponseHelper::success($txs, 'get single transcation');
    }

    /* ------------------ SYNC HELPERS ------------------ */

    private function syncPaidOrdersIntoTransactions(int $userId): void
    {
        $orders = \App\Models\Order::where('user_id', $userId)
            ->where('payment_status', 'paid')
            ->get();

        foreach ($orders as $order) {
            Transaction::updateOrCreate(
                [
                    'user_id'       => $userId,
                    'title'         => 'Order #' . ($order->order_number ?? $order->id),
                    'transacted_at' => $order->created_at,
                ],
                [
                    'amount'    => $order->total_price ?? $order->amount ?? 0,
                    'status'    => $order->payment_status ?? 'paid',
                    'type'      => 'deposit',
                    'method'    => $order->payment_method ?? 'unknown',
                    'tx_id'     => $order->gateway_txn_id ?? $order->reference ?? null,
                    'reference' => $order->reference ?? null,
                ]
            );
        }
    }

    private function syncPaidOrdersIntoTransactionsForAll(): void
    {
        $orders = \App\Models\Order::where('payment_status', 'paid')->get();

        foreach ($orders as $order) {
            Transaction::updateOrCreate(
                [
                    'user_id'       => (int)$order->user_id,
                    'title'         => 'Order #' . ($order->order_number ?? $order->id),
                    'transacted_at' => $order->created_at,
                ],
                [
                    'amount'    => $order->total_price ?? $order->amount ?? 0,
                    'status'    => $order->payment_status ?? 'paid',
                    'type'      => 'deposit',
                    'method'    => $order->payment_method ?? 'unknown',
                    'tx_id'     => $order->gateway_txn_id ?? $order->reference ?? null,
                    'reference' => $order->reference ?? null,
                ]
            );
        }
    }

    /* ------------------ FETCH HELPERS ----------------- */

    private function fetchAndFormatAll(?string $type = null, ?string $status = null, ?string $q = null): array
    {
        $query = Transaction::with('user:id,first_name,sur_name,email,phone,profile_picture,user_code'); // <— removed name

        if ($type)   $query->where('type', $type);
        if ($status) $query->where('status', $status);
        if ($q) {
            $query->where(function ($x) use ($q) {
                $x->where('title', 'like', "%{$q}%")
                  ->orWhere('method', 'like', "%{$q}%")
                  ->orWhere('tx_id', 'like', "%{$q}%")
                  ->orWhere('reference', 'like', "%{$q}%");
            });
        }

        $list = $query->latest('transacted_at')->get();

        $summary = [
            'total_transactions' => $list->count(),
            'total_deposits'     => (int)$list->where('type', 'deposit')->sum('amount'),
            'total_withdrawals'  => (int)$list->where('type', 'withdrawal')->sum('amount'),
            'status_counts'      => $list->groupBy('status')->map->count(),
        ];

        $rows = $list->map(fn(Transaction $t) => $this->row($t))->values();
        return [$summary, $rows];
    }

    private function fetchAndFormatForUser(int $userId, ?string $type = null, ?string $status = null, ?string $q = null): array
    {
        $query = Transaction::with('user:id,first_name,sur_name,email,phone,profile_picture,user_code') // <— removed name
            ->where('user_id', $userId);

        if ($type)   $query->where('type', $type);
        if ($status) $query->where('status', $status);
        if ($q) {
            $query->where(function ($x) use ($q) {
                $x->where('title', 'like', "%{$q}%")
                  ->orWhere('method', 'like', "%{$q}%")
                  ->orWhere('tx_id', 'like', "%{$q}%")
                  ->orWhere('reference', 'like', "%{$q}%");
            });
        }

        $list = $query->latest('transacted_at')->get();

        $summary = [
            'total_transactions' => $list->count(),
            'total_deposits'     => (int)$list->where('type', 'deposit')->sum('amount'),
            'total_withdrawals'  => (int)$list->where('type', 'withdrawal')->sum('amount'),
            'status_counts'      => $list->groupBy('status')->map->count(),
        ];

        $rows = $list->map(fn(Transaction $t) => $this->row($t))->values();
        return [$summary, $rows];
    }

    private function row(Transaction $t): array
    {
        [$name] = $this->extractUserBasics($t);

        return [
            'id'             => $t->id,
            'name'           => $name,
            'payment_method' => $t->method,
            'price'          => (string)$t->amount,
            'date'           => optional($t->transacted_at)->format('Y-m-d'),
            'time'           => optional($t->transacted_at)->format('H:i:s'),
            'type'           => $t->type,
            'tx_id'          => $t->tx_id ?? $t->reference ?? (string)$t->id,
            'status'         => $t->status,
            'title'          => $t->title,
        ];
    }

    private function extractUserBasics(Transaction $t): array
    {
        $u = $t->user;
        $name = $u
            ? trim(($u->first_name ?? '').' '.($u->sur_name ?? '')) // build display name from existing columns
            : '—';

        return [$name, $u->email ?? null, $u->phone ?? null];
    }
}