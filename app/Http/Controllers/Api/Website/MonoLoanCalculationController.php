<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\MonoLoanCalculationRequest;
use App\Models\LoanCalculation;
use App\Models\MonoLoanCalculation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonoLoanCalculationController extends Controller
{
    public function store(MonoLoanCalculationRequest $request, string $id)
    {
        try{

            $data = $request->validated();
            $loanCalculation = LoanCalculation::where('id', $id)->firstOrFail();

            // $loanCalculation = LoanCalculation::findorfail($id);
            $monoLoan = MonoLoanCalculation::create([
                'down_payment' => $data['down_payment'],
                'loan_calculation_id' => $id,
                'loan_amount' => $loanCalculation->loan_amount,
                'repayment_duration' => $loanCalculation->repayment_duration,
            ]);

            $monoLoanCalculation = MonoLoanCalculation::where('loan_calculation_id', $id)
                ->with('loanCalculation')->first();
                // dd($monoLoanCalculation);
                 Log::info("not store the mono loan");
                return ResponseHelper::success($monoLoanCalculation, "Store Mono Loan Calculation");
        }
        catch(Exception $ex)
        {
            Log::error("not store the mono loan". $ex->getMessage());
            return ResponseHelper::error("Don't store the mono loan calculation");
        }
    }
}
