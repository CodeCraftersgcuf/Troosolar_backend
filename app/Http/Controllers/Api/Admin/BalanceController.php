<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Order;
use Exception;

class BalanceController extends Controller
{
    public function index()
    {
        try {
            $wallets = Wallet::with('user')->get();

            $balances = [];
            $totalLoan = 0;
            $totalShopping = 0;

            foreach ($wallets as $wallet) {
                $user = $wallet->user;

                $firstName = $user->first_name ?? 'N/A';

                // Transactions
                $topup = (int) Transaction::where('user_id', $wallet->user_id)
                    ->where('type', 'deposit')
                    ->sum('amount');

                $withdrawalFromTransactions = (int) Transaction::where('user_id', $wallet->user_id)
                    ->where('type', 'withdrawal')
                    ->sum('amount');

                // Withdrawals from orders table
                $withdrawalFromOrders = (int) Order::where('user_id', $wallet->user_id)
                    ->where('payment_method', 'withdrawal')
                    ->where('payment_status', 'paid')
                    ->sum('total_price');

                $totalWithdrawal = $withdrawalFromTransactions + $withdrawalFromOrders;

                $loan = (int) $wallet->loan_balance;
                $shopping = (int) $wallet->shopping_balance;
                $main = (int) $wallet->main_balance;

                // Add to summary
                $totalLoan += $loan;
                $totalShopping += $shopping;

                $balances[] = [
                    'first_name' => $firstName,
                    'loan_balance' => $loan,
                    'main_balance' => $main,
                    'total_topup' => $topup,
                    'total_withdrawal' => $totalWithdrawal,
                ];
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'summary' => [
                        'total_loan_balance' => $totalLoan,
                        'total_shopping_balance' => $totalShopping,
                    ],
                    'balances' => $balances
                ],
                'message' => 'Balances fetched successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve balances',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}