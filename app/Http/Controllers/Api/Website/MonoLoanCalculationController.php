<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\MonoLoanCalculationRequest;
use App\Models\InterestPercentage;
use App\Models\LoanApplication;
use App\Models\LoanCalculation;
use App\Models\MonoLoanCalculation;
use App\Models\Transaction;
use App\Models\Wallet;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonoLoanCalculationController extends Controller
{
    public function store(string $id)
    {
        try {

            // $data = $request->validated();
            $loanCalculation = LoanCalculation::where('id', $id)->firstOrFail();
            $downPayment = $loanCalculation->loan_amount * 0.25;
            $interestPercentage = InterestPercentage::latest()->first();
            $percentage = $interestPercentage->interest_percentage;
            $interest = $loanCalculation->loan_amount * $percentage;
            $totalAmount = $loanCalculation->loan_amount + $interest;
            // $loanCalculation = LoanCalculation::findorfail($id);
            $monoLoan = MonoLoanCalculation::create([
                'down_payment' => $downPayment,
                'loan_calculation_id' => $id,
                'loan_amount' => $loanCalculation->loan_amount,
                'repayment_duration' => $loanCalculation->repayment_duration,
                'total_amount' => $totalAmount,
                'interest_rate' => $percentage,
                'status' => 'pending'
            ]);
            $loanCalculation->status = 'offered';
            $loanCalculation->save();

            $monoLoanCalculation = MonoLoanCalculation::where('loan_calculation_id', $id)
                ->with('loanCalculation')->first();
            return ResponseHelper::success($monoLoanCalculation, "Store Mono Loan Calculation");
        } catch (Exception $ex) {
            Log::error("not store the mono loan" . $ex->getMessage());
            return ResponseHelper::error("Don't store the mono loan calculation");
        }
    }

    public function edit(Request $request, string $id)
    {
        try {
            $monoLoanCalculation = MonoLoanCalculation::where('loan_calculation_id', $id)
                ->with('loanCalculation')->firstOrFail();
            // dd($monoLoanCalculation);
            $product_amount = $request->input('product_amount', $monoLoanCalculation->loanCalculation->product_amount);
            $loan_amount = $request->input('loan_amount', $monoLoanCalculation->loan_amount);
            $duration = $request->input('repayment_duration', $monoLoanCalculation->repayment_duration);
            $downPayment = $loan_amount * 0.25;
            $monthlyPayment = $duration > 0 ? round($loan_amount / $duration, 2) : 0;
            $repaymentDate = Carbon::now()->addMonth();
            // update
            $monoLoanCalculation->loanCalculation->product_amount = $product_amount;
            $monoLoanCalculation->loanCalculation->loan_amount = $loan_amount;
            $monoLoanCalculation->loanCalculation->repayment_duration = $duration;
            $monoLoanCalculation->loanCalculation->monthly_payment = $monthlyPayment;
            $monoLoanCalculation->loanCalculation->repayment_date = $repaymentDate;

            $monoLoanCalculation->loan_amount = $loan_amount;
            $monoLoanCalculation->repayment_duration = $duration;
            $monoLoanCalculation->down_payment = $downPayment;

            // $monoLoanCalculation->loanCalculation->save();

            $monoLoanCalculation->save();
            return ResponseHelper::success($monoLoanCalculation, "Single Mono Loan Calculation");
        } catch (Exception $ex) {
            return ResponseHelper::error("Don't edit the mono loan calculation");
        }
    }
    public function grant($id)
    {
        try {
            $monoLoanCalculation = MonoLoanCalculation::where('id', $id)->firstOrFail();
            $monoLoanCalculation->status = 'approved';
            $monoLoanCalculation->save();
            $loanCalculation = LoanCalculation::where('id', $monoLoanCalculation->loan_calculation_id)->first();
            $loanCalculation->status = 'approved';
            $loanCalculation->save();
            $userId=$loanCalculation->user_id;
            $wallet = Wallet::where('user_id', $userId)->first();
            $wallet->loan_balance = $wallet->loan_balance + $monoLoanCalculation->loan_amount;
            $wallet->save();
            $loanApplication = LoanApplication::where('user_id', $userId)->first();
            $loanApplication->status = 'approved';
            $loanApplication->save();
            $transaction=new Transaction([
                'user_id'=>$userId,
                'amount'=>$monoLoanCalculation->loan_amount,
                'tx_id'=>date('ymdhis').rand(1000,9999),
                "title"=>"Loan Granted",
                "type"=>"incoming",
                "status"=>"paid",
                "method"=>"Direct",
                "transacted_at"=>now()   
            ]);
            return ResponseHelper::success($monoLoanCalculation, "Single Mono Loan Calculation");
        } catch (Exception $ex) {
            Log::error("not edit the mono loan" . $ex->getMessage());
            return ResponseHelper::error("Don't edit the mono loan calculation");
        }
    }
}
