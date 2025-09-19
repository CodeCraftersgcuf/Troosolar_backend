<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanDistributedRequest;
use App\Models\LoanApplication;
use App\Models\LoanDistribute;
use App\Models\LoanStatus;
use App\Models\MonoLoanCalculation;
use App\Models\Wallet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoanDistributedController extends Controller
{
    // store the loan distributed

    public function store(LoanDistributedRequest $request, string $loanApplicationId)
    {
        try
        {
            $data = $request->validated();
            $loanApplication = LoanApplication::where('id', $loanApplicationId)->firstOrFail();
            if (!$loanApplication) {
                return ResponseHelper::error('Loan application not found');
            }
            $loanApplication->update([
                'status' => 'active',
            ]);
            $loanDistributed = LoanDistribute::create([
                'distribute_amount'=> $data['distribute_amount'],
                'status' => $data['status'],
                'reject_reason'=> $data['reject_reason'] ?? null,
                'loan_application_id' => $loanApplicationId
            ]);
            
            $loanStatus = LoanStatus::where('loan_application_id', $loanApplicationId)->update([
                'disbursement_status' => 'active',
                'disbursement_date' => now(),

            ]);
            $wallet = Wallet::where('user_id', Auth::id())->latest()->first();
            if ($wallet) {
                $wallet->update([
                    'loan_balance' => $wallet->loan_balance + $data['distribute_amount'],
                ]);
            } 

            $monoLoanCalculation = MonoLoanCalculation::where('loan_application_id', $loanApplicationId)
                ->latest()->update([
                    'loan_amount' => $data['distribute_amount'],
                    'status' => 'active',
                ]);
            return ResponseHelper::success($loanDistributed, 'loan distributed succesfully');
        }
        catch(Exception $ex)
        {
            Log::error('Error in LoanDistributedController@store: ' . $ex->getMessage());
             return ResponseHelper::error( 'loan distributed is not added');
        }
    }

    // show all loan distributed
    public function allLoansDistributed()
{
    try {
        $allLoans = LoanStatus::with('loan_application.mono')->get();

        $totalLoans = LoanApplication::count();
        $loanDistributed = LoanDistribute::count();
        $loanDistributedAmount = LoanDistribute::sum('distribute_amount');
        $loans = [
            'total loans' => $totalLoans,
            'loan distributed' => $loanDistributed,
            'amount distributed ' => $loanDistributedAmount
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
                'name' => $loan->loan_application->beneficiary_name,
                'user_id' => $loan->loan_application->user_id,
                'mono_id' => $loan->loan_application->mono->id,
                'Duration' => $loan->loan_application->repayment_duration.' months',
                'date' => $loan->loan_application->created_at->format('Y-m-d'),
                'disbursement status' => $loan->disbursement_status,
                'approval status' => $loan->approval_status
            ];
        }

        $merged = array_merge($loans, $data);
        return ResponseHelper::success($merged, 'All Loan Status');
    } catch (Exception $e) {
        return ResponseHelper::error('Failed to retrieve loan statuses', 500);
    }
}
}