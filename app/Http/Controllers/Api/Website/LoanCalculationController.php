<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanCalculationRequest;
use App\Models\InterestPercentage;
use App\Models\LoanCalculation;
use App\Models\LoanCalculationProduct;
use App\Models\LoanInstallment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoanCalculationController extends Controller
{
      public function store(LoanCalculationRequest $requst)
    {
       try{
         $data = $requst->validated();
          $repaymentDate = Carbon::now()->addMonth();
         $interestPercentage= InterestPercentage::latest()->first();
         $interestPercentageRate = $interestPercentage->interest_percentage;
         $monthlyPaymnet = $data['loan_amount'] / $data['repayment_duration'];
        $monthlyPaymnet = round($monthlyPaymnet, 2);
        $loan = LoanCalculation::create([
            'product_amount' => $data['product_amount'],
            'loan_amount' => $data['loan_amount'],
            'repayment_duration' => $data['repayment_duration'],
            'user_id' => Auth::id(),
            'interest_percentage_id' => $interestPercentageRate,
            'repayment_date' => $repaymentDate,
            'interest_percentage' => $interestPercentageRate,
            'monthly_payment' => $monthlyPaymnet,
            'status'=>'pending'
        ]); 
       
        return response()->json([
            'status' => 'success',
             'message' => 'Loan calculate successfully',
             'repayment date' => $repaymentDate,
             'data' => $loan,
                
        ]);
       }
       catch(Exception $e){
        Log::error('no save loan calculation'.$e->getMessage());
        return ResponseHelper::error('Loan Calculation is not added');
       }
    }
    public function status(){
      $user=Auth::user();
      $loan = LoanCalculation::where('user_id', $user->id)->latest()->first();
      $exists=false;
      if($loan){
        $exists=true;
      }
      return response()->json([
        'status' => $loan->status,
        'exists' => $exists,
        'message' => 'Loan calculate successfully',
        'data' => $loan,
      ]);
    }

    
}