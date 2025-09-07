<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\LoanApplication;
use App\Models\LoanStatus;
use App\Models\LoanDistribute;
use App\Models\MonoLoanCalculation;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\LoanInstallment;

class AnalyticController extends Controller
{
    public function index()
    {
        try {
            // ✅ General Metrics
            $totalUsers = User::count();
            $activeUsers = User::where('created_at', '>=', now()->subDays(30))->count();
            $totalOrders = Order::count();
            $deletedAccounts = User::onlyTrashed()->count();

            // ✅ Loan Statistics
            $totalLoans = LoanApplication::count();
            $approvedLoans = LoanStatus::where('approval_status', 'approved')->count();
            $rejectedLoans = LoanStatus::where('approval_status', 'rejected')->count();
            $totalAmountDisbursed = LoanDistribute::sum('distribute_amount');

            // ✅ Overdue Loans
            $overdueLoans = LoanInstallment::where('status', 'overdue')->count();
            $overdueAmount = LoanInstallment::where('status', 'overdue')->sum('amount');

            $data = [
                'general' => [
                    'total_users'           => $totalUsers,
                    'active_users'          => $activeUsers,
                    'total_orders'          => $totalOrders,
                    'bounce_rate'   => 200,
                    'deleted_accounts'      => $deletedAccounts,
                    'total_revenue'         => 'N'. 20000,
                    'total_deposits'       => 'N'. 20000,
                    'total_withdrawals'    => 'N'. 20000,
                    'admin_earning'    => 'N'. 20000,
                    'top_selling_products' => 'Inverter',
                    'total_loans'           => $totalLoans,
                    'approved_loans'        => $approvedLoans,
                    'rejected_loans'        => $rejectedLoans,
                    'total_amount_disbursed'=> $totalAmountDisbursed,
                    'top_partner'           => 'ABC Partner',
                    'overdue_loans'         => $overdueLoans,
                    'overdue_amount'        => $overdueAmount,
                    'loan_default_rate' => '50%',
                    'repayment_completion_rate' => '50%',
                    'total_revenue' => 'revenue by product',
                    'delivery_fees' => 'installment fees',
                    'revenue_growth_rate' => '40%',
                    'interest_rate' => 'N 10000',
                    
                ],
            ];

            return ResponseHelper::success($data, 'Analytics data fetched successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to fetch analytics data: ' . $e->getMessage(), 500);
        }
    }
}