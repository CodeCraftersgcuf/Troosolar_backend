<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanCalculationRequest;
use App\Http\Requests\ToolCalculatorRequest;
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
use SebastianBergmann\Type\FalseType;

class LoanCalculationController extends Controller
{


public function store(LoanCalculationRequest $request)
{
    try {
        $data = $request->validated();

        // 0) Ensure we have an interest row
        $interestPercentage = InterestPercentage::latest()->first();
        if (!$interestPercentage) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Interest rate configuration not found.'
            ], 422);
        }

        // 1) Check for pending loan
        $pendingLoan = LoanCalculation::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->first();

        if ($pendingLoan) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You already have a pending loan request. Please wait until it is processed.'
            ], 422);
        }

        // 2) Check for calculated loan
        $calculatedLoan = LoanCalculation::where('user_id', Auth::id())
            ->where('status', 'calculated')
            ->first();

        $repaymentDate           = Carbon::now()->addMonth();
        $interestPercentageRate  = (float) $interestPercentage->interest_percentage; // e.g. 12.5
        $repaymentDuration       = (int) $data['repayment_duration'];
        $loanAmount              = (float) $data['loan_amount'];

        // Your current approach ignores interest for monthly_payment:
        $monthlyPayment          = round($loanAmount / max($repaymentDuration, 1), 2);

        if ($calculatedLoan) {
            $calculatedLoan->update([
                'product_amount'         => $data['product_amount'],
                'loan_amount'            => $loanAmount,
                'repayment_duration'     => $repaymentDuration,
                'interest_percentage_id' => $interestPercentage->id,
                'repayment_date'         => $repaymentDate,
                'interest_percentage'    => $interestPercentageRate,
                'monthly_payment'        => $monthlyPayment,
                'status'                 => 'calculated',
            ]);
            $loan = $calculatedLoan;
        } else {
            $loan = LoanCalculation::create([
                'product_amount'         => $data['product_amount'],
                'loan_amount'            => $loanAmount,
                'repayment_duration'     => $repaymentDuration,
                'user_id'                => Auth::id(),
                'interest_percentage_id' => $interestPercentage->id,
                'repayment_date'         => $repaymentDate,
                'interest_percentage'    => $interestPercentageRate,
                'monthly_payment'        => $monthlyPayment,
                'status'                 => 'calculated',
            ]);
        }

        // --- Extra fields for the response ---
        $downPayment = round($monthlyPayment * 0.25, 2);                       // as requested: payment * 0.25
        $totalAmount = round($monthlyPayment * $repaymentDuration, 2);         // equals principal with your formula

        return response()->json([
            'status'         => 'success',
            'message'        => 'Loan calculated successfully',
            'repayment_date' => $repaymentDate,
            'data'           => array_merge($loan->toArray(), [
                'interest_rate' => $interestPercentageRate,
                'down_payment'  => $downPayment,
                'total_amount'  => $totalAmount,
            ]),
        ]);

    } catch (\Throwable $e) {
        Log::error('Loan calculation save failed: '.$e->getMessage());
        return ResponseHelper::error('Loan Calculation could not be saved');
    }
}

public function tool(ToolCalculatorRequest $request)
{
    try {
        $data = $request->validated();

        // 0) Ensure we have an interest row
        $interestPercentage = InterestPercentage::latest()->first();
        if (!$interestPercentage) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Interest rate configuration not found.'
            ], 422);
        }

        // Extract key values
        $repaymentDate          = Carbon::now()->addMonth();
        $interestPercentageRate = (float) $interestPercentage->interest_percentage; // e.g. 12.5
        $repaymentDuration      = (int) $data['repayment_duration'];
        $loanAmount             = (float) $data['loan_amount'];

        // Same monthly payment formula (no interest added in your current logic)
        $monthlyPayment         = round($loanAmount / max($repaymentDuration, 1), 2);

        // Derived fields
        $downPayment = round($monthlyPayment * 0.25, 2);
        $totalAmount = round($monthlyPayment * $repaymentDuration, 2);

        // ✅ Return the same structure as `store`, but without saving anything
        return response()->json([
            'status'         => 'success',
            'message'        => 'Loan calculated successfully (no record created)',
            'repayment_date' => $repaymentDate,
            'data'           => [
                'product_amount'      => $data['product_amount'],
                'loan_amount'         => $loanAmount,
                'repayment_duration'  => $repaymentDuration,
                'interest_percentage' => $interestPercentageRate,
                'monthly_payment'     => $monthlyPayment,
                'interest_rate'       => $interestPercentageRate,
                'down_payment'        => $downPayment,
                'total_amount'        => $totalAmount,
            ],
        ]);

    } catch (\Throwable $e) {
        Log::error('Tool loan calculation failed: ' . $e->getMessage());
        return ResponseHelper::error('Loan Calculation could not be generated'.$e->getMessage() ,);
    }
}


    public function status(){
      $user=Auth::user();
      $loan = LoanCalculation::where('user_id', $user->id)->latest()->first();
      if(!$loan){
        return ResponseHelper::success([
        'status' => 'not_exists',
        'exists' => false,
        'message' => 'You have not applied for any loan',
        'data' => [],
        // 'monoLoanCalculation' => $monoLoanCalculation
        ]);
      //  / return ResponseHelper::error("Loan is not calculated");
      }
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
    public function monoLoanCalculations(){
        try{
            $totalCalculations=LoanCalculation::count();
            $approvedCalculations=LoanCalculation::where('status','approved')->count();
            $offeredCalculations=LoanCalculation::where('status','offered')->count();
            $pendingCalculations=LoanCalculation::where('status','pending')->count();
            $loanCalculations=LoanCalculation::where('status','pending')->with('user')->latest()->get();
            $summary=[
                'total_calculations'=>$totalCalculations,
                'approved_calculations'=>$approvedCalculations,
                'offered_calculations'=>$offeredCalculations,
                'pending_calculations'=>$pendingCalculations
            ];
            return ResponseHelper::success([
                'loan_calculations'=>$loanCalculations,
                'summary'=>$summary
            ], "Store Mono Loan Calculation");
        }catch(Exception $ex){
            Log::error("not store the mono loan". $ex->getMessage());
            return ResponseHelper::error("Don't store the mono loan calculation");
        }
    }
}