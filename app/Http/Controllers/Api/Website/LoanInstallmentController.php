<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\LoanInstallment;
use App\Models\MonoLoanCalculation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoanInstallmentController extends Controller
{
    public function loanInstallment(Request $request, string $monoLoanCalculationId)
    {
        try{
            $data = $request->all();
            $monoLoanCalculation = MonoLoanCalculation::where('id', $monoLoanCalculationId)
                ->latest()->first();
                if(!$monoLoanCalculation)
                {
                    return ResponseHelper::error('User is no installment');
                }
            $LoanInstallment = LoanInstallment::create([
                'user_id' => Auth::id(),
                'status' => $data['status'],
                'mono_calculation_id' => $monoLoanCalculationId
            ]);
            $LoanInstallment->load(['user', 'monoLoanCalculation']);
            Log::info('User have installment');
            return ResponseHelper::success($LoanInstallment, 'The installment of user');
        }
        catch(Exception $ex)
        {
            return ResponseHelper::error('something is wrong in Loan Installment');
        }
    }

    // show installment
       public function show($monoCalculationId)
{
    try {
        // Fetch the loan repayment details
        
        $LoanInstallment = LoanInstallment::where('mono_calculation_id', $monoCalculationId)->get();

        if ($LoanInstallment->isEmpty()) {
            return ResponseHelper::error('No loan repayment found', 404);
        }
        
            $data = [];
        foreach ($LoanInstallment as $index => $installment) {
            $data[] = [
                'installment' => $index + 1, // Starts from 1
                'status' => $installment->status,
                'amount' => $installment->amount,
                'created_at' => $installment->created_at
            ];
        }
        
        return ResponseHelper::success($data, 'Loan repayment details retrieved successfully');
    } catch (Exception $ex) {
        Log::error('Error in LoanRepaymentController@show: ' . $ex->getMessage());
        return ResponseHelper::error('Failed to retrieve loan repayment details');
    }
}
}
