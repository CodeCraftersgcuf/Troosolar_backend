<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanCalculationRequest;
use App\Models\InterestPercentage;
use App\Models\LoanCalculation;
use App\Models\LoanCalculationProduct;
use App\Models\LoanInstallment;
use App\Models\MonoLoanCalculation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoanCalculationController extends Controller
{


public function store(LoanCalculationRequest $request)
{
    try {
        $data = $request->validated();

        // 1️⃣ Check for pending loan
        $pendingLoan = LoanCalculation::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->first();
        if ($pendingLoan) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You already have a pending loan request. Please wait until it is processed.'
            ], 422);
        }

        // 2️⃣ Check for calculated loan
        $calculatedLoan = LoanCalculation::where('user_id', Auth::id())
            ->where('status', 'calculated')
            ->first();

        $repaymentDate         = Carbon::now()->addMonth();
        $interestPercentage    = InterestPercentage::latest()->first();
        $interestPercentageRate = $interestPercentage->interest_percentage;
        $monthlyPayment        = round($data['loan_amount'] / $data['repayment_duration'], 2);

        // 3️⃣ Update or create
        if ($calculatedLoan) {
            $calculatedLoan->update([
                'product_amount'        => $data['product_amount'],
                'loan_amount'           => $data['loan_amount'],
                'repayment_duration'    => $data['repayment_duration'],
                'interest_percentage_id'=> $interestPercentage->id,
                'repayment_date'        => $repaymentDate,
                'interest_percentage'   => $interestPercentageRate,
                'monthly_payment'       => $monthlyPayment,
                'status'                => 'calculated',
            ]);
            $loan = $calculatedLoan;
        } else {
            $loan = LoanCalculation::create([
                'product_amount'        => $data['product_amount'],
                'loan_amount'           => $data['loan_amount'],
                'repayment_duration'    => $data['repayment_duration'],
                'user_id'               => Auth::id(),
                'interest_percentage_id'=> $interestPercentage->id,
                'repayment_date'        => $repaymentDate,
                'interest_percentage'   => $interestPercentageRate,
                'monthly_payment'       => $monthlyPayment,
                'status'                => 'calculated',
            ]);
        }

        return response()->json([
            'status'         => 'success',
            'message'        => 'Loan calculated successfully',
            'repayment_date' => $repaymentDate,
            'data'           => $loan,
        ]);
    } catch (Exception $e) {
        Log::error('Loan calculation save failed: '.$e->getMessage());
        return ResponseHelper::error('Loan Calculation could not be saved');
    }
}

    public function status(){
      $user=Auth::user();
      $loan = LoanCalculation::where('user_id', $user->id)->latest()->first();
      $exists=false;
      if($loan){
        $exists=true;
      }
      $monoLoanCalculation=[];
      if($loan->status=='offered'){
        $monoLoanCalculation = MonoLoanCalculation::where('loan_calculation_id', $loan->id)
            ->with('loanCalculation')->first();
      }
      return response()->json([
        'status' => $loan->status,
        'exists' => $exists,
        'message' => 'Loan calculate successfully',
        'data' => $loan,
        'monoLoanCalculation' => $monoLoanCalculation
      ]);
    }
    public function finalized($id){
  
      $loan = LoanCalculation::findorfail($id);
      $loan->status='pending';
      $loan->save();
      return response()->json([
        'status' => 'success',
        'message' => 'Loan calculate successfully',
        'data' => $loan,
      ]);
    }
    public function offeredLoanCalculation(){
      try{
        $user=Auth::user();
        $loanCalculatedByUser=LoanCalculation::where('user_id', $user->id)->latest()->first();
        if($loanCalculatedByUser->status!='offered'){
          return ResponseHelper::error("Loan is not offered");
        }
        $monoLoanCalculation=MonoLoanCalculation::where('loan_calculation_id', $loanCalculatedByUser->id)->latest()->first();
        return ResponseHelper::success($monoLoanCalculation, "Store Mono Loan Calculation");
      }catch(Exception $ex){
        Log::error("not store the mono loan". $ex->getMessage());
        return ResponseHelper::error("Don't store the mono loan calculation");
      }
    }    
}