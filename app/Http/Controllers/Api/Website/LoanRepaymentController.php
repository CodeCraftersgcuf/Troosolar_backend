<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanRepaymentRequest;
use App\Models\InterestPercentage;
use App\Models\LoanDistribute;
use App\Models\LoanInstallment;
use App\Models\LoanRepayment;
use App\Models\MonoLoanCalculation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoanRepaymentController extends Controller
{
    
public function store(LoanRepaymentRequest $request, $monoCalculationId)
{
   try{
     $data = $request->validated();

    // Check if mono loan calculation exists
    $monoLoanCalculation = MonoLoanCalculation::find($monoCalculationId);

    if (!$monoLoanCalculation) {
        return ResponseHelper::error('Mono loan calculation not found', 404);
    }

    // Create the loan repayment
    $repayment = LoanRepayment::create([
        'amount' => $data['amount'],
        'user_id' => Auth::id(),
        'mono_calculation_id' => $monoCalculationId,
    ]);

    $installment = LoanInstallment::where('mono_calculation_id', $monoCalculationId)->latest()->first();
       $installment->create([
            'amount' => $data['amount'],
            'status' => 'paid',
            'user_id' => Auth::id(),
            'mono_calculation_id' => $monoCalculationId,
            'remaining_duration' => $installment->remaining_duration - 1,
        ]);

    // interest percentage
    $interestPercentage = InterestPercentage::latest()->first();

    // distribute date
    $monoLoan = MonoLoanCalculation::where('id', $monoCalculationId)->latest()->first();
    $distributeDate = LoanDistribute::where('loan_application_id', $monoLoan->loan_application_id)->latest()->first();

    // update mono loan calculation
    $monoLoanCalculation = MonoLoanCalculation::find($monoCalculationId);
    $monoLoanCalculation->update([
        'loan_amount' => $monoLoanCalculation->loan_amount - $data['amount'],
        // 'status' => 'active',
    ]);
        $data = [
            'Loan status' => $monoLoanCalculation->status,
            'Loan amount' => $monoLoanCalculation->loan_amount,
            'Repayment duration' => $monoLoanCalculation->repayment_duration,
            'Interest percentage' => $interestPercentage->interest_percentage,
            'Disbursment date' => $distributeDate->disbursement_date,
        ];

    return ResponseHelper::success($data, 'Loan repayment recorded successful');
   }
   catch(Exception $ex)
   {
    Log::error('Error in LoanRepaymentController@store: ' . $ex->getMessage());
    return ResponseHelper::error('You are not Loan Repayment');
   }
}

}

