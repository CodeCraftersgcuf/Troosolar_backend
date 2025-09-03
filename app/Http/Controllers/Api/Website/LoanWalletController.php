<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\LoanApplication;
use App\Models\Wallet;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoanWalletController extends Controller
{
     public function show()
    {
        try
        { 
            $loanWallet = Wallet::where('user_id', Auth::id())->first();
               if (!$loanWallet) {
            return ResponseHelper::error('Loan wallet not found.', 404);
        }
          if($loanWallet->loan_balance === null){
            $loanWallet->loan_balance = 0;
          };
          $data = [
            'Loan balance' => $loanWallet->loan_balance,
          ];
            return ResponseHelper::success($data, 'Your loan Wallet');

        }
        catch(Exception $ex)
        {
            Log::error('Error in LoanWalletController@show: ' . $ex->getMessage());
            return ResponseHelper::error('something wrog in loan wallet');
        }
    }

    public function loanDashboard($loanApplicationId)
    {
      try{
        $loanApplicationId = LoanApplication::where('id', $loanApplicationId)
            ->where('user_id', Auth::id())
            ->first();
            
        if (!$loanApplicationId) {
            return ResponseHelper::error('Loan application not found', 404);
        }
        $loanAmount = $loanApplicationId->loan_amount;
        $duration = $loanApplicationId->repayment_duration;
        $data = [
            'loan_amount' => $loanAmount,
            'repayment_duration' => $duration,
        ];
        return ResponseHelper::success($data, 'Loan application details retrieved successfully');
      }
       catch(Exception $ex)
        {
            Log::error('loan dashboard is not retrieved '. $ex->getMessage());
            return ResponseHelper::error('something wrong in loan wallet');
        }
    }
}