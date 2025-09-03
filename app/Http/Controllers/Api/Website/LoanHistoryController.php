<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Models\LoanHistory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanHistoryController extends Controller
{
  public function show(string $loanApplicationId)
{
    try {
        $userId = Auth::id();

        // Get the loan calculation related to this application
        $loanApplication = LoanApplication::with(['loanCalculation.monoLoanCalculation'])
            ->where('id', $loanApplicationId)
            ->where('user_id', $userId)
            ->first();

        if (!$loanApplication) {
            return ResponseHelper::error("No loan application found for this user", 404);
        }

        $loanCalculation = $loanApplication->loanCalculation;
        $monoLoanCalculation = $loanCalculation?->monoLoanCalculation;

        // Get Loan Distribution Info
        $distribution = $loanCalculation?->loanDistributed;
        $disbursementDate = optional($distribution)->created_at;

        // Get Loan Repayments
        $repayments = $monoLoanCalculation?->loanRepayments()
            ->select('amount', 'status', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // Return structured response
        return ResponseHelper::success([
            'loan_details' => [
                'status' => optional($distribution)->status ?? 'Pending',
                'loan_amount' => $loanCalculation->loan_amount ?? 0,
                'interest_rate' => $loanCalculation->interest_percentage . '%',
                'loan_period' => $loanCalculation->repayment_duration . ' months',
                'disbursement_date' => optional($disbursementDate)->format('F j, Y'),
            ],
            'repayments' => $repayments->map(function ($repayment) {
                return [
                    'status' => $repayment->status,
                    'date' => $repayment->created_at->format('d F, y'),
                    'amount' => $repayment->amount,
                ];
            }),
        ], "Loan History");

    } catch (Exception $ex) {
    return ResponseHelper::error('Something is wrong: unable to fetch history');
    }
}

}