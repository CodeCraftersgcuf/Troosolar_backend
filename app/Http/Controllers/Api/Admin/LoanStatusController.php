<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\InterestPercentage;
use App\Models\LoanApplication;
use App\Models\LoanDistribute;
use App\Models\LoanStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoanStatusController extends Controller
{
    // show all loan status
public function allLoansStatus()
{
    try {
        $allLoans = LoanStatus::with('loan_application')->get();

        $totalLoans = LoanApplication::count();
        $loanSend = LoanStatus::where('send_status', 'active')->count();
        $loanApproved = LoanStatus::where('approval_status', 'active')->count();

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
                'name' => $loan->loan_application->beneficiary_name,
                'Amount' => $loan->loan_application->loan_amount,
                'date' => $loan->loan_application->created_at->format('Y-m-d'),
                'send status' => $loan->send_status,
                'approval status' => $loan->approval_status
            ];
        }

        $merged = array_merge($loans, $data);
        return ResponseHelper::success($merged, 'All Loan Status');
    } catch (Exception $e) {
        return ResponseHelper::error('Failed to retrieve loan statuses', 500);
    }
}

    // fullLoanDetails
public function fullLoanDetails(string $id)
{
    try
    {
        $loanDetails = LoanStatus::where('id', $id)->with('loan_application')->first();
        
        $interestRate = InterestPercentage::latest()->first();
        $loanDetails = [
            'id' => $loanDetails->loan_application->id,
            'name' => $loanDetails->loan_application->beneficiary_name,
            'loan limit' => $loanDetails->loan_application->loan_limit,
            'amount' => $loanDetails->loan_application->loan_amount,
            'repayment duration' => $loanDetails->loan_application->repayment_duration,
            'interest rate' => $interestRate->interest_percentage,
            'financing partner' => 'allied bank',
            'send_status' => $loanDetails->send_status,
            'send_date' => $loanDetails->send_date,
            'approval_status' => $loanDetails->approval_status,
            'approval_date' => $loanDetails->approval_date,
            'disbursement_status' => $loanDetails->disbursement_status,
            'disbursement_date' => $loanDetails->disbursement_date
        ];
        return ResponseHelper::success($loanDetails, 'Loan Details Retrieved Successfully');    
    }
    catch (Exception $e) {
        Log::error('Error retrieving loan details: ' . $e->getMessage());
        return ResponseHelper::error('Failed to retrieve loan details', 500);
    }
}


}