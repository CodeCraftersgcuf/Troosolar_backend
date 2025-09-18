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
    // GET /api/transactions - Returns ALL transactions
    public function index(Request $request)
    {
        try {
            // Try to sync all orders into transactions (with error handling)
            $user=Auth::user();
            if($user->role=='admin'){
                
                $transactions=Transaction::with('user')->latest()->get();;
            }{
                $transactions=Transaction::where('user_id','=',$user->id)->latest()->get();
            }
            $totalTransactions=Transaction::count();
            $totalUsersWithTransactions=$transactions->pluck('user_id')->unique()->count();
            $totalAmount=$transactions->sum('amount');
            return response()->json([
                'status'       => true,
                'summary'      => [
                    'total_transactions' => $totalTransactions,
                    'total_users_with_transactions' => $totalUsersWithTransactions,
                    'total_amount' => $totalAmount
                ],
                'transactions' => $transactions,
                'message'      => 'All transactions fetched successfully'
            ]);

           
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Model not found in transactions index: '.$e->getMessage());
            return ResponseHelper::error('Transaction data not found', 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in transactions index: '.$e->getMessage());
            return ResponseHelper::error('Database error occurred while fetching transactions', 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in transactions index: '.$e->getMessage());
            return ResponseHelper::error('An unexpected error occurred while fetching transactions', 500);
        } catch (\Throwable $e) {
            Log::error('Critical error in transactions index: '.$e->getMessage());
            return ResponseHelper::error('A critical error occurred while processing the request', 500);
        }
    }
    public function getforUser(){
        try{
            $user=Auth::user();
            $transactions=Transaction::where('user_id','=',$user->id)->latest()->get();
            return response()->json([
                'status'       => true,
              
                'transactions' => $transactions,
                'message'      => 'All transactions fetched successfully'
            ]);

        }catch(\Exception $e){
            Log::error('Unexpected error in transactions index: '.$e->getMessage());
            return ResponseHelper::error('An unexpected error occurred while fetching transactions', 500);
        }
    }

    // GET /api/transactions/user/{userId}
    public function forUser(int $userId, Request $request)
    {
        try {
            // First, verify the user exists with wallet information
            $user = \App\Models\User::with('wallet')->find($userId);
            if (!$user) {
                return ResponseHelper::error('User not found', 404);
            }

            // Sync all types of transactions for this user
            $this->syncPaidOrdersIntoTransactions($userId);
            $this->syncLoanRepaymentsIntoTransactions($userId);
            $this->syncLoanDisbursementsIntoTransactions($userId);

            [$summary, $rows] = $this->fetchAndFormatForUser(
                userId: $userId,
                type:   $request->query('type'),
                status: $request->query('status'),
                q:      $request->query('q')
            );

            return response()->json([
                'status'       => true,
                'user_info'    => [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->sur_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'user_code' => $user->user_code,
                    'profile_picture' => $user->profile_picture,
                    'is_verified' => $user->is_verified,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at
                ],
                'wallet_info'  => [
                    'loan_balance' => $user->wallet ? $user->wallet->loan_balance : 0,
                    'shop_balance' => $user->wallet ? $user->wallet->shop_balance : 0,
                    'wallet_status' => $user->wallet ? $user->wallet->status : 'inactive'
                ],
                'summary'      => $summary,
                'transactions' => $rows,
                'message'      => 'All transactions fetched successfully for user '.$userId,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('User not found in transactions: '.$e->getMessage());
            return ResponseHelper::error('User not found', 404);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error in transactions: '.$e->getMessage());
            return ResponseHelper::error('Database error occurred while fetching transactions', 500);
        } catch (\Exception $e) {
            Log::error('Unexpected error in transactions forUser: '.$e->getMessage());
            return ResponseHelper::error('An unexpected error occurred while fetching user transactions', 500);
        } catch (\Throwable $e) {
            Log::error('Critical error in transactions forUser: '.$e->getMessage());
            return ResponseHelper::error('A critical error occurred while processing the request', 500);
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
        try {
            $orders = \App\Models\Order::where('payment_status', 'paid')->get();

            foreach ($orders as $order) {
                try {
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
                } catch (\Exception $e) {
                    Log::error('Error syncing order to transaction: ' . $e->getMessage(), [
                        'order_id' => $order->id,
                        'user_id' => $order->user_id
                    ]);
                    // Continue with other orders even if one fails
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in syncPaidOrdersIntoTransactionsForAll: ' . $e->getMessage());
            // Don't throw the exception, just log it to prevent breaking the main flow
        }
    }

    private function syncLoanRepaymentsIntoTransactions(int $userId): void
    {
        try {
            $repayments = \App\Models\LoanRepayment::where('user_id', $userId)->get();

            foreach ($repayments as $repayment) {
                Transaction::updateOrCreate(
                    [
                        'user_id'       => $userId,
                        'title'         => 'Loan Repayment #' . $repayment->id,
                        'transacted_at' => $repayment->created_at,
                    ],
                    [
                        'amount'    => $repayment->amount,
                        'status'    => $repayment->status ?? 'completed',
                        'type'      => 'withdrawal',
                        'method'    => 'loan_repayment',
                        'tx_id'     => 'LR-' . $repayment->id,
                        'reference' => 'Loan Repayment ID: ' . $repayment->id,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Error syncing loan repayments for user ' . $userId . ': ' . $e->getMessage());
            // Don't throw the exception, just log it to prevent breaking the main flow
        }
    }

    private function syncLoanDisbursementsIntoTransactions(int $userId): void
    {
        try {
            // Get loan applications for this user
            $loanApplications = \App\Models\LoanApplication::where('user_id', $userId)->get();
            
            foreach ($loanApplications as $loanApp) {
                // Get loan distributions for this application
                $distributions = \App\Models\LoanDistribute::where('loan_application_id', $loanApp->id)->get();
                
                foreach ($distributions as $distribution) {
                    Transaction::updateOrCreate(
                        [
                            'user_id'       => $userId,
                            'title'         => 'Loan Disbursement #' . $distribution->id,
                            'transacted_at' => $distribution->created_at,
                        ],
                        [
                            'amount'    => $distribution->distribute_amount,
                            'status'    => $distribution->status ?? 'completed',
                            'type'      => 'deposit',
                            'method'    => 'loan_disbursement',
                            'tx_id'     => 'LD-' . $distribution->id,
                            'reference' => 'Loan Application ID: ' . $loanApp->id,
                        ]
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Error syncing loan disbursements for user ' . $userId . ': ' . $e->getMessage());
            // Don't throw the exception, just log it to prevent breaking the main flow
        }
    }

    /* ------------------ FETCH HELPERS ----------------- */

    private function fetchAndFormatAll(?string $type = null, ?string $status = null, ?string $q = null): array
    {
        try {
            $query = Transaction::with('user:id,first_name,sur_name,email,phone,profile_picture,user_code');

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

            // Calculate additional metrics
            $totalUsersWithTransactions = $list->pluck('user_id')->unique()->count();
            $totalUsersInSystem = \App\Models\User::count(); // Count all users in the system
            $totalTransactionAmount = (int)$list->sum('amount');

            $summary = [
                'total_transactions'     => $list->count(),
                'total_users'           => $totalUsersInSystem,
                // 'total_users_with_transactions' => $totalUsersWithTransactions,
                'total_transaction_amount' => $totalTransactionAmount,
                'total_deposits'        => (int)$list->where('type', 'deposit')->sum('amount'),
                'total_withdrawals'     => (int)$list->where('type', 'withdrawal')->sum('amount'),
                'status_counts'         => $list->groupBy('status')->map->count(),
            ];

            $rows = $list->map(fn(Transaction $t) => $this->row($t))->values();
            return [$summary, $rows];
        } catch (\Exception $e) {
            Log::error('Error in fetchAndFormatAll: ' . $e->getMessage());
            // Return empty data instead of throwing
            return [
                [
                    'total_transactions'     => 0,
                    'total_users'           => 0,
                    'total_users_with_transactions' => 0,
                    'total_transaction_amount' => 0,
                    'total_deposits'        => 0,
                    'total_withdrawals'     => 0,
                    'status_counts'         => [],
                ],
                []
            ];
        }
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