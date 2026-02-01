<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\InterestPercentage;
use App\Models\LoanApplication;
use App\Models\LoanDistribute;
use App\Models\LoanInstallment;
use App\Models\LoanStatus;
use App\Models\MonoLoanCalculation;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoanStatusController extends Controller
{
    // show all loan status
    public function allLoansStatus()
    {
        try {
            $allLoans = LoanStatus::with('loan_application.mono.loanCalculation')->get();

            $totalLoans = LoanApplication::count();
            $loanSend = LoanStatus::where('send_status', 'active')->count();
            $loanApproved = LoanStatus::where('approval_status', 'active')->count();
            $monoLoan = MonoLoanCalculation::where('lo');
            $loans = [
                'total loans' => $totalLoans,
                'loan send' => $loanSend,
                'loan approved' => $loanApproved
            ];
            $data = [];

            foreach ($allLoans as $loan) {
                // Check if the loan application exists
                if (!$loan->loan_application) {
                    Log::warning("Missing loan_application for loan_status ID: {$loan->id}");
                    continue;
                }

                $data[] = [
                    'id' => $loan->id,
                    'loan_application_id'=> $loan->loan_application_id,
                    'loan_calculation_id' => $loan->loan_application->mono->loanCalculation->id,
                    'user_id' => $loan->loan_application->user_id,
                    'name' => $loan->loan_application->beneficiary_name,
                    'amount' => $loan->loan_application->mono->loan_amount,
                    'date' => $loan->loan_application->created_at->format('Y-m-d'),
                    'send_status' => $loan->send_status,
                    'approval_status' => $loan->approval_status
                ];
            }

            $merged = array_merge($loans, $data);
            return ResponseHelper::success($merged, 'All Loan Status');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve loan statuses', 500);
        }
    }

    // fullLoanDetails - Get all loans for a specific user
  public function fullLoanDetails(string $id)
{
    try {
        // User + wallet
        $user = \App\Models\User::with('wallet')->find($id);
        if (!$user) {
            return ResponseHelper::error('User not found', 404);
        }

        // All loans for user
        $loanApplications = LoanApplication::where('user_id', $id)
            ->with(['loanStatus', 'user', 'mono']) // mono must link to MonoLoanCalculation
            ->get();

        if ($loanApplications->isEmpty()) {
            return ResponseHelper::success([
                'user_info' => [
                    'id' => $user->id,
                    'name' => trim(($user->first_name ?? '').' '.($user->sur_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'wallet_info' => [
                    'loan_balance' => optional($user->wallet)->loan_balance ?? 0,
                    'shop_balance' => optional($user->wallet)->shop_balance ?? 0,
                    'wallet_status' => optional($user->wallet)->status ?? 'inactive',
                ],
                'total_loans' => 0,
                'this_month_installments_summary' => [
                    'month' => now()->format('Y-m'),
                    'count' => 0,
                    'total_amount' => 0,
                    'by_status' => [],
                ],
                'loans' => [],
            ], 'No loans found for this user');
        }

        // Interest %
        $interestRate = InterestPercentage::latest()->first();

        // === New: Fetch this month's installments (single query) ===
        $start = Carbon::now('Asia/Karachi')->startOfMonth();
        $end   = Carbon::now('Asia/Karachi')->endOfMonth();

        /** @var \Illuminate\Support\Collection<int,\App\Models\LoanInstallment> $thisMonthInstallments */
        $thisMonthInstallments = LoanInstallment::where('user_id', $id)
            ->whereBetween('created_at', [$start, $end])
            ->get();

        // Group by mono_calculation_id for quick lookup
        $byMonoId = $thisMonthInstallments->groupBy('mono_calculation_id');

        // Build a root summary for this month
        $rootCount = $thisMonthInstallments->count();
        $rootTotal = (float) $thisMonthInstallments->sum(function ($i) {
            return (float) ($i->amount ?? 0);
        });
        $rootByStatus = $thisMonthInstallments
            ->groupBy(fn($i) => $i->status ?? 'unknown')
            ->map->count()
            ->toArray();

        $allLoanDetails = [];

        foreach ($loanApplications as $loanApp) {
            // Common loan fields
            $loanDetails = [
                'loan_application_id'   => $loanApp->id,
                'user_id'               => $loanApp->user_id,
                'amount'                => optional($loanApp->mono)->amount, // safe if mono is null
                'user_name'             => trim(optional($loanApp->user)->first_name.' '.optional($loanApp->user)->sur_name),
                'beneficiary_name'      => $loanApp->beneficiary_name,
                'beneficiary_email'     => $loanApp->beneficiary_email,
                'beneficiary_phone'     => $loanApp->beneficiary_phone,
                'beneficiary_relationship'=> $loanApp->beneficiary_relationship,
                'loan_limit'            => $loanApp->loan_limit,
                'loan_amount'           => $loanApp->loan_amount,
                'repayment_duration'    => $loanApp->repayment_duration,
                'interest_rate'         => optional($interestRate)->interest_percentage,
                'financing_partner'     => 'allied bank',
                'application_status'    => $loanApp->status,
                'created_at'            => $loanApp->created_at,
                'updated_at'            => $loanApp->updated_at,
            ];

            // Loan status block - use BNPL status from loan_applications for send_status
            $loanDetails['loan_status'] = $loanApp->loanStatus ? [
                'send_status'         => $loanApp->status, // Use BNPL status from loan_applications (pending, approved, rejected, counter_offer, counter_offer_accepted)
                'send_date'           => $loanApp->loanStatus->send_date,
                'approval_status'     => $loanApp->loanStatus->approval_status,
                'approval_date'       => $loanApp->loanStatus->approval_date,
                'disbursement_status' => $loanApp->loanStatus->disbursement_status,
                'disbursement_date'   => $loanApp->loanStatus->disbursement_date,
            ] : [
                'send_status'         => $loanApp->status, // Use BNPL status even if loanStatus doesn't exist
                'send_date'           => null,
                'approval_status'     => null,
                'approval_date'       => null,
                'disbursement_status' => null,
                'disbursement_date'   => null,
            ];

            // === New: Per-loan "this month" installments ===
            $monoId = optional($loanApp->mono)->id; // may be null if relation missing
            $itemsForThisLoan = $monoId ? ($byMonoId->get($monoId) ?? collect()) : collect();

            $loanDetails['this_month_installments'] = [
                'month'        => $start->format('Y-m'),
                'count'        => $itemsForThisLoan->count(),
                'total_amount' => (float) $itemsForThisLoan->sum(fn($i) => (float) ($i->amount ?? 0)),
                'by_status'    => $itemsForThisLoan->groupBy(fn($i) => $i->status ?? 'unknown')
                                    ->map->count()
                                    ->toArray(),
                'items'        => $itemsForThisLoan->map(fn($i) => [
                    'id'                 => $i->id,
                    'mono_calculation_id'=> $i->mono_calculation_id,
                    'status'             => $i->status,
                    'amount'             => (float) ($i->amount ?? 0),
                    'created_at'         => $i->created_at,
                ])->values(),
            ];

            $allLoanDetails[] = $loanDetails;
        }

        return ResponseHelper::success([
            'user_info' => [
                'id'    => $user->id,
                'name'  => trim(($user->first_name ?? '').' '.($user->sur_name ?? '')),
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'wallet_info' => [
                'loan_balance'  => optional($user->wallet)->loan_balance ?? 0,
                'shop_balance'  => optional($user->wallet)->shop_balance ?? 0,
                'wallet_status' => optional($user->wallet)->status ?? 'inactive',
            ],
            'this_month_installments_summary' => [
                'month'        => $start->format('Y-m'),
                'count'        => $rootCount,
                'total_amount' => $rootTotal,
                'by_status'    => $rootByStatus,
                'period'       => [
                    'start' => $start->toDateTimeString(),
                    'end'   => $end->toDateTimeString(),
                    'tz'    => 'Asia/Karachi',
                ],
            ],
            'total_loans' => count($allLoanDetails),
            'loans'       => $allLoanDetails,
        ], 'All Loan Details Retrieved Successfully');
    } catch (\Throwable $e) {
        Log::error('Error retrieving loan details: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return ResponseHelper::error('Failed to retrieve loan details', 500);
    }
}

    public function singleLoanDetail($id)
    {
        try {
            $loan = LoanApplication::with(['loanStatus', 'user', 'mono'])->find($id);

            if (!$loan) {
                return ResponseHelper::error('Loan not found', 404);

            }
            return ResponseHelper::success($loan, 'Loan details retrieved successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to retrieve loan details', 500);
        }
    }
}
