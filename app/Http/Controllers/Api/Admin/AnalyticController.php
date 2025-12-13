<?php

namespace App\Http\Controllers\Api\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Bundles;
use App\Models\Transaction;
use App\Models\LoanApplication;
use App\Models\LoanStatus;
use App\Models\LoanDistribute;
use App\Models\LoanInstallment;
use App\Models\LoanRepayment;
use App\Models\MonoLoanCalculation;
use App\Models\Partner;
use App\Models\WithdrawRequest;
use App\Models\UserActivity;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class AnalyticController extends Controller
{
    /**
     * Get comprehensive analytics data
     * GET /api/admin/analytics?period=all_time|daily|weekly|monthly|yearly
     */
    public function index(Request $request)
    {
        try {
            $period = $request->get('period', 'all_time'); // all_time, daily, weekly, monthly, yearly

            // Get date range based on period
            $dateRange = $this->getDateRange($period);
            $previousDateRange = $this->getPreviousDateRange($period);

            // General Analytics
            $general = $this->getGeneralAnalytics($dateRange, $previousDateRange);

            // Financial Analytics
            $financial = $this->getFinancialAnalytics($dateRange, $previousDateRange);

            // Revenue Analytics
            $revenue = $this->getRevenueAnalytics($dateRange, $previousDateRange);

            return ResponseHelper::success([
                'period' => $period,
                'date_range' => [
                    'start' => $dateRange['start']->toDateString(),
                    'end' => $dateRange['end']->toDateString(),
                ],
                'general' => $general,
                'financial' => $financial,
                'revenue' => $revenue,
            ], 'Analytics data fetched successfully');

        } catch (Exception $e) {
            Log::error('Analytics Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return ResponseHelper::error('Failed to fetch analytics data: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get date range based on period
     */
    private function getDateRange($period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'daily':
                return [
                    'start' => $now->copy()->startOfDay(),
                    'end' => $now->copy()->endOfDay(),
                ];
            case 'weekly':
                return [
                    'start' => $now->copy()->startOfWeek(),
                    'end' => $now->copy()->endOfWeek(),
                ];
            case 'monthly':
                return [
                    'start' => $now->copy()->startOfMonth(),
                    'end' => $now->copy()->endOfMonth(),
                ];
            case 'yearly':
                return [
                    'start' => $now->copy()->startOfYear(),
                    'end' => $now->copy()->endOfYear(),
                ];
            default: // all_time
                return [
                    'start' => Carbon::parse('2020-01-01'), // Adjust based on your oldest record
                    'end' => $now,
                ];
        }
    }

    /**
     * Get previous period date range for comparison
     */
    private function getPreviousDateRange($period)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'daily':
                return [
                    'start' => $now->copy()->subDay()->startOfDay(),
                    'end' => $now->copy()->subDay()->endOfDay(),
                ];
            case 'weekly':
                return [
                    'start' => $now->copy()->subWeek()->startOfWeek(),
                    'end' => $now->copy()->subWeek()->endOfWeek(),
                ];
            case 'monthly':
                return [
                    'start' => $now->copy()->subMonth()->startOfMonth(),
                    'end' => $now->copy()->subMonth()->endOfMonth(),
                ];
            case 'yearly':
                return [
                    'start' => $now->copy()->subYear()->startOfYear(),
                    'end' => $now->copy()->subYear()->endOfYear(),
                ];
            default:
                return null;
        }
    }

    /**
     * Get General Analytics
     */
    private function getGeneralAnalytics($dateRange, $previousDateRange)
    {
        // Total Users (all time)
        $totalUsers = User::count();

        // Active Users (users with activity in last 30 days)
        $activeUsers = User::whereHas('activitys', function ($query) {
            $query->where('created_at', '>=', Carbon::now()->subDays(30));
        })->orWhere('created_at', '>=', Carbon::now()->subDays(30))->count();

        // Total Orders (all time)
        $totalOrders = Order::count();

        // Orders in period
        $ordersInPeriod = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();

        // Deleted Accounts (soft deletes if enabled, otherwise 0)
        $deletedAccounts = 0; // User::onlyTrashed()->count(); // Uncomment if soft deletes enabled

        // Bounce Rate: Users who registered but never placed an order (approximation)
        $usersWithOrders = User::has('orders')->count();
        $bounceRate = $totalUsers > 0 ? round((($totalUsers - $usersWithOrders) / $totalUsers) * 100, 2) : 0;

        // Total Revenue (from confirmed orders)
        $totalRevenue = Order::where('payment_status', 'confirmed')
            ->sum('total_price');

        // Revenue in period
        $revenueInPeriod = Order::where('payment_status', 'confirmed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('total_price');

        // Total Deposits (incoming transactions)
        $totalDeposits = Transaction::where('type', 'incoming')
            ->where('status', 'Completed')
            ->sum('amount');

        // Deposits in period
        $depositsInPeriod = Transaction::where('type', 'incoming')
            ->where('status', 'Completed')
            ->whereBetween('transacted_at', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');

        // Total Withdrawals (from WithdrawRequest)
        $totalWithdrawals = WithdrawRequest::where('status', 'approved')
            ->sum('amount');

        // Withdrawals in period
        $withdrawalsInPeriod = WithdrawRequest::where('status', 'approved')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');

        // Admin Earnings (can be calculated as revenue - withdrawals - costs)
        // For now, using total revenue as approximation
        $adminEarnings = $totalRevenue; // Adjust based on your business logic

        // Top Selling Product (all time)
        $topProduct = OrderItem::select('itemable_id', 'itemable_type', DB::raw('SUM(quantity) as total_quantity'))
            ->where('itemable_type', Product::class)
            ->whereHas('order', function ($query) {
                $query->where('payment_status', 'confirmed');
            })
            ->groupBy('itemable_id', 'itemable_type')
            ->orderBy('total_quantity', 'desc')
            ->first();

        $topSellingProduct = 'N/A';
        if ($topProduct) {
            $product = Product::find($topProduct->itemable_id);
            $topSellingProduct = $product ? $product->title : 'N/A';
        }

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'total_orders' => $totalOrders,
            'orders_in_period' => $ordersInPeriod,
            'bounce_rate' => $bounceRate,
            'deleted_accounts' => $deletedAccounts,
            'total_revenue' => number_format($totalRevenue, 2),
            'revenue_in_period' => number_format($revenueInPeriod, 2),
            'total_deposits' => number_format($totalDeposits, 2),
            'deposits_in_period' => number_format($depositsInPeriod, 2),
            'total_withdrawals' => number_format($totalWithdrawals, 2),
            'withdrawals_in_period' => number_format($withdrawalsInPeriod, 2),
            'admin_earnings' => number_format($adminEarnings, 2),
            'top_selling_product' => $topSellingProduct,
        ];
    }

    /**
     * Get Financial Analytics
     */
    private function getFinancialAnalytics($dateRange, $previousDateRange)
    {
        // Total Loans (all applications)
        $totalLoans = LoanApplication::count();

        // Loans in period
        $loansInPeriod = LoanApplication::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count();

        // Approved Loans
        $approvedLoans = LoanApplication::where('status', 'approved')->count();
        $approvedLoansInPeriod = LoanApplication::where('status', 'approved')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // Rejected Loans
        $rejectedLoans = LoanApplication::where('status', 'rejected')->count();
        $rejectedLoansInPeriod = LoanApplication::where('status', 'rejected')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // Pending Loans
        $pendingLoans = LoanApplication::where('status', 'pending')->count();
        $pendingLoansInPeriod = LoanApplication::where('status', 'pending')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->count();

        // Loan Amount Disbursed
        $totalAmountDisbursed = LoanDistribute::sum('distribute_amount');
        $amountDisbursedInPeriod = LoanDistribute::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('distribute_amount');

        // Top Partner (by number of loans sent)
        $topPartnerData = Partner::orderBy('no_of_loans', 'desc')->first();
        $topPartner = $topPartnerData ? $topPartnerData->name : 'N/A';

        // Overdue Loans (installments that are overdue)
        // Count unique loan calculations with overdue installments
        $overdueLoans = LoanInstallment::where('status', 'pending')
            ->whereDate('payment_date', '<', Carbon::now()->toDateString())
            ->distinct('mono_calculation_id')
            ->count('mono_calculation_id');

        $overdueAmount = LoanInstallment::where('status', 'pending')
            ->whereDate('payment_date', '<', Carbon::now()->toDateString())
            ->sum('amount');

        // Loan Default Rate: Overdue loans / Total approved loans
        $loanDefaultRate = $approvedLoans > 0 
            ? round(($overdueLoans / $approvedLoans) * 100, 2) 
            : 0;

        // Repayment Completion Rate: Paid installments / Total installments
        $totalInstallments = LoanInstallment::count();
        $paidInstallments = LoanInstallment::where('status', 'paid')->count();
        $repaymentCompletionRate = $totalInstallments > 0 
            ? round(($paidInstallments / $totalInstallments) * 100, 2) 
            : 0;

        return [
            'total_loans' => $totalLoans,
            'loans_in_period' => $loansInPeriod,
            'approved_loans' => $approvedLoans,
            'approved_loans_in_period' => $approvedLoansInPeriod,
            'rejected_loans' => $rejectedLoans,
            'rejected_loans_in_period' => $rejectedLoansInPeriod,
            'pending_loans' => $pendingLoans,
            'pending_loans_in_period' => $pendingLoansInPeriod,
            'loan_amount_disbursed' => number_format($totalAmountDisbursed, 2),
            'amount_disbursed_in_period' => number_format($amountDisbursedInPeriod, 2),
            'top_partner' => $topPartner,
            'overdue_loans' => $overdueLoans,
            'overdue_loan_amount' => number_format($overdueAmount, 2),
            'loan_default_rate' => $loanDefaultRate,
            'repayment_completion_rate' => $repaymentCompletionRate,
        ];
    }

    /**
     * Get Revenue Analytics
     */
    private function getRevenueAnalytics($dateRange, $previousDateRange)
    {
        // Total Revenue (from confirmed orders)
        $totalRevenue = Order::where('payment_status', 'confirmed')
            ->sum('total_price');

        // Revenue in period
        $revenueInPeriod = Order::where('payment_status', 'confirmed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('total_price');

        // Revenue by Product (from order items)
        $revenueByProduct = OrderItem::select('itemable_id', 'itemable_type', DB::raw('SUM(subtotal) as total_revenue'))
            ->whereHas('order', function ($query) {
                $query->where('payment_status', 'confirmed');
            })
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->where('itemable_type', Product::class)
            ->groupBy('itemable_id', 'itemable_type')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get()
            ->filter(function ($item) {
                return $item->itemable_id !== null;
            })
            ->map(function ($item) {
                $product = Product::find($item->itemable_id);
                return [
                    'product_id' => $item->itemable_id,
                    'product_name' => $product ? $product->title : 'Unknown Product',
                    'revenue' => number_format($item->total_revenue ?? 0, 2),
                ];
            })
            ->values();

        // Delivery Fees
        $deliveryFees = Order::where('payment_status', 'confirmed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('delivery_fee');

        // Installation Fees
        $installationFees = Order::where('payment_status', 'confirmed')
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('installation_price');

        // Revenue Growth Rate
        $revenueGrowthRate = 0;
        if ($previousDateRange) {
            $previousRevenue = Order::where('payment_status', 'confirmed')
                ->whereBetween('created_at', [$previousDateRange['start'], $previousDateRange['end']])
                ->sum('total_price');

            if ($previousRevenue > 0) {
                $revenueGrowthRate = round((($revenueInPeriod - $previousRevenue) / $previousRevenue) * 100, 2);
            } elseif ($revenueInPeriod > 0) {
                $revenueGrowthRate = 100; // Infinite growth from 0
            }
        }

        // Interest Earned (from loan repayments)
        // Interest = total_amount - loan_amount in MonoLoanCalculation
        // We calculate interest earned from loans that have been disbursed and have repayments
        $interestEarned = 0;
        
        // Method 1: Calculate from MonoLoanCalculation (total_amount - loan_amount) for approved/disbursed loans
        $totalInterestFromLoans = MonoLoanCalculation::whereHas('loanRepayments')
            ->get()
            ->sum(function ($loan) {
                // Interest = total_amount - loan_amount
                return max(0, ($loan->total_amount ?? 0) - ($loan->loan_amount ?? 0));
            });
        
        // Method 2: Calculate from total repayments vs principal
        // Sum all repayments and subtract principal (loan_amount)
        $totalRepayments = LoanRepayment::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');
        
        // Use the more accurate method (total interest from loans)
        $interestEarned = $totalInterestFromLoans > 0 ? $totalInterestFromLoans : $totalRepayments;

        return [
            'total_revenue' => number_format($totalRevenue, 2),
            'revenue_in_period' => number_format($revenueInPeriod, 2),
            'revenue_by_product' => $revenueByProduct,
            'delivery_fees' => number_format($deliveryFees ?? 0, 2),
            'installation_fees' => number_format($installationFees ?? 0, 2),
            'revenue_growth_rate' => $revenueGrowthRate,
            'interests_earned' => number_format($interestEarned, 2),
        ];
    }
}
