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

    // fullLoanDetails - Get all loans for a specific user
public function fullLoanDetails(string $id)
{
    try
    {
        // First, verify the user exists with wallet information
        $user = \App\Models\User::with('wallet')->find($id);
        if (!$user) {
            return ResponseHelper::error('User not found', 404);
        }

        // Get all loan applications for this user with their loan status
        $loanApplications = LoanApplication::where('user_id', $id)
            ->with(['loanStatus', 'user'])
            ->get();
        
        if ($loanApplications->isEmpty()) {
            return ResponseHelper::success([], 'No loans found for this user');
        }

        $interestRate = InterestPercentage::latest()->first();
        $allLoanDetails = [];

        foreach ($loanApplications as $loanApp) {
            $loanDetails = [
                'loan_application_id' => $loanApp->id,
                'user_id' => $loanApp->user_id,
                'user_name' => $loanApp->user->first_name . ' ' . $loanApp->user->sur_name,
                'beneficiary_name' => $loanApp->beneficiary_name,
                'beneficiary_email' => $loanApp->beneficiary_email,
                'beneficiary_phone' => $loanApp->beneficiary_phone,
                'beneficiary_relationship' => $loanApp->beneficiary_relationship,
                'loan_limit' => $loanApp->loan_limit,
                'loan_amount' => $loanApp->loan_amount,
                'repayment_duration' => $loanApp->repayment_duration,
                'interest_rate' => $interestRate ? $interestRate->interest_percentage : null,
                'financing_partner' => 'allied bank',
                'application_status' => $loanApp->status,
                'created_at' => $loanApp->created_at,
                'updated_at' => $loanApp->updated_at
            ];

            // Add loan status details if they exist
            if ($loanApp->loanStatus) {
                $loanDetails['loan_status'] = [
                    'send_status' => $loanApp->loanStatus->send_status,
                    'send_date' => $loanApp->loanStatus->send_date,
                    'approval_status' => $loanApp->loanStatus->approval_status,
                    'approval_date' => $loanApp->loanStatus->approval_date,
                    'disbursement_status' => $loanApp->loanStatus->disbursement_status,
                    'disbursement_date' => $loanApp->loanStatus->disbursement_date
                ];
            } else {
                $loanDetails['loan_status'] = null;
            }

            $allLoanDetails[] = $loanDetails;
        }

        return ResponseHelper::success([
            'user_info' => [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->sur_name,
                'email' => $user->email,
                'phone' => $user->phone
            ],
            'wallet_info' => [
                'loan_balance' => $user->wallet ? $user->wallet->loan_balance : 0,
                'shop_balance' => $user->wallet ? $user->wallet->shop_balance : 0,
                'wallet_status' => $user->wallet ? $user->wallet->status : 'inactive'
            ],
            'total_loans' => count($allLoanDetails),
            'loans' => $allLoanDetails
        ], 'All Loan Details Retrieved Successfully');    
    }
    catch (Exception $e) {
        Log::error('Error retrieving loan details: ' . $e->getMessage());
        return ResponseHelper::error('Failed to retrieve loan details', 500);
    }
}


}