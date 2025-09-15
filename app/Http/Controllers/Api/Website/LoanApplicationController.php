<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoanApplicationRequest;
use App\Models\InterestPercentage;
use App\Models\LoanApplication;
use App\Models\LoanCalculation;
use App\Models\LoanInstallment;
use App\Models\LoanStatus;
use App\Models\MonoLoanCalculation;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\LoanInstallmentScheduler;


class LoanApplicationController extends Controller
{
    // upload documnets 
    public function documents(LoanApplicationRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            if (isset($data['upload_document']) && $data['upload_document']->isValid()) {
                $img = $data['upload_document'];
                $ext = $img->getClientOriginalExtension();
                $imageName = time() . '.' . $ext;
                $img->move(public_path('/loan_applications'), $imageName);
                $data['upload_document'] = 'loan_applications/' . $imageName;
            }
            $monoLoanCalculation = MonoLoanCalculation::find($id);

            $loanApplication = LoanApplication::create([
                'title_document'      => $data['title_document'],
                'upload_document'     => $data['upload_document'],
                'user_id'             => Auth::id(),
                'mono_loan_calculation' => $id, // **keep your name**
                'loan_amount'         => $monoLoanCalculation->loan_amount,
            ]);
            $loanCalCulation = LoanCalculation::find($monoLoanCalculation->loan_calculation_id);
            $loanCalCulation->status = 'submitted';
            $loanCalCulation->save();
            // Generate installments for this MonoLoanCalculation id ($id)
            // firstPaymentDate = null => will use LoanCalculation->repayment_date or now()->addMonth()
            $schedule = LoanInstallmentScheduler::generate((int)$id);
            $loanStatus = LoanStatus::create([
                'loan_application_id' => $loanApplication->id,
                'send_status' => 'pending',
                'approval_status' => 'pending',
                'disbursement_status' => 'pending'
            ]);
            return ResponseHelper::success(
                [
                    'loan_application' => $loanApplication,
                    'installments'     => $schedule,
                ],
                'Loan Application documents submitted and schedule created'
            );
        } catch (\Throwable $ex) {
            Log::error('no save the loan application ' . $ex->getMessage());
            return ResponseHelper::error('Loan Application documents is not submitted');
        }
    }

    // beneficiary details
    public function beneficiary(LoanApplicationRequest $request, string $id)
    {
        try {
            $data = $request->validated();
            $beneficiary = LoanApplication::where('mono_loan_calculation', $id)
                ->update([
                    'beneficiary_name' => $data['beneficiary_name'],
                    'beneficiary_email' => $data['beneficiary_email'],
                    'beneficiary_phone' => $data['beneficiary_phone'],
                    'beneficiary_relationship' => $data['beneficiary_relationship'],
                ]);
            return ResponseHelper::success($beneficiary, 'Successfully added the beneficiary details');
        } catch (Exception $ex) {
            Log::error("dfuwe" . $ex->getMessage());
            return ResponseHelper::error("Doesn't added the beneficiary detail");
        }
    }
    // loan details
    public function loanDetail(LoanApplicationRequest $request, string $id)
    {
        try {
            $data = $request->validated();
            $loanApplicationId = LoanApplication::where('mono_loan_calculation', $id)->first();
            $loanDetail = LoanApplication::where('mono_loan_calculation', $id)
                ->update([
                    'loan_amount'  => $data['loan_amount'],
                    'repayment_duration' => $data['repayment_duration'],
                ]);
            $loanStatus = LoanStatus::create([
                'loan_application_id' => $loanApplicationId->id
            ]);
            // installment
            $installment = LoanInstallment::create([
                'user_id' => Auth::id(),
                'mono_calculation_id' => $id,
                'remaining_duration' => $data['repayment_duration'],
            ]);

            $loanAmount = $data['loan_amount'];
            $repaymentDuration = $data['repayment_duration'];
            $MonthlyPercentage = $loanAmount / $repaymentDuration;

            $interestPercentage = InterestPercentage::latest()->first();
            $interestPercentageRate = $interestPercentage->interest_percentage;

            $repaymentDate = Carbon::now()->addMonth()->toDateString();

            $monoLoanCalculation = MonoLoanCalculation::where('id', $id)
                ->update([
                    'loan_application_id' => $loanApplicationId->id
                ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Loan detail is added',
                'loan amount' => $data['loan_amount'],
                'duration' => $data['repayment_duration'],
                'monthly percentage' => $MonthlyPercentage,
                'interest percentage' => $interestPercentageRate,
                'repayment date' => $repaymentDate
            ]);
        } catch (Exception $ex) {
            Log::error('nor add loan detail' . $ex->getMessage());
            return ResponseHelper::error('Loan application not add the loan details');
        }
    }
    public function loanKycDetails($userId){
        try {
            $loanApplication= LoanApplication::where('user_id', $userId)->first();
            $user=User::where('id',$userId)->first();
            
            return ResponseHelper::success([
                'loan_application' => $loanApplication,
                'user' => $user
            ], 'Loan application is added');
            
        }catch(Exception $ex){
            return ResponseHelper::error('Loan application not add the loan details');
        }
    }

    // delete loan application
    public function destory(string $id)
    {
        try {
            $applicationId = LoanApplication::where('id', $id)->delete();
            return ResponseHelper::success($applicationId, 'Loan application is deleted successfully');
        } catch (Exception $ex) {
            return ResponseHelper::error('Loan application isn"t deleted');
        }
    }

    // all loan application
    public function allLoanApplication()
    {
        try {
            $allLoanApplication = LoanApplication::all();
            return ResponseHelper::success($allLoanApplication, "all the Loan Applications");
        } catch (Exception $ex) {
            return ResponseHelper::error('do not show all Loan applications');
        }
    }
    // single loan application
    public function singleLoanApplication($id)
    {
        try {
            $allLoanApplication = LoanApplication::where('id', $id)->get();
            if ($allLoanApplication->isEmpty()) {
                return ResponseHelper::error('do not exist Loan applications');
            }
            return ResponseHelper::success($allLoanApplication, "single Loan Applications");
        } catch (Exception $ex) {
            return ResponseHelper::error('do not show single Loan applications');
        }
    }

    public function singleDocument($mono_loan_calculation_id)
    {
        try {
            $singleDocument = LoanApplication::where('mono_loan_calculation', $mono_loan_calculation_id)->latest()->first();

            // dd($singleDocument);
            $data = [

                'title_document' => $singleDocument->title_document,
                'upload_document' => $singleDocument->upload_document
            ];
            return ResponseHelper::success($data, "single Loan Applications");
        } catch (Exception $ex) {
            Log::error('Error in LoanApplicationController@singleDocument: ' . $ex->getMessage());
            return ResponseHelper::error('do not show single Loan applications');
        }
    }

    // single beneficiary
    public function singleBeneficiary($mono_loan_calculation_id)
    {
        try {
            $singleDocument = LoanApplication::where('mono_loan_calculation', $mono_loan_calculation_id)->latest()->first();

            // dd($singleDocument);
            $data = [

                'Beneficiary name' => $singleDocument->beneficiary_name,
                'Beneficiary email' => $singleDocument->beneficiary_email,
                'Beneficiary phone' => $singleDocument->beneficiary_phone,
                'Beneficiary relation' => $singleDocument->beneficiary_relationship,
            ];
            return ResponseHelper::success($data, "single Beneficiary Detail");
        } catch (Exception $ex) {
            Log::error('Error in LoanApplicationController@singleBeneficiary: ' . $ex->getMessage());
            return ResponseHelper::error('do not show single beneficiary applications');
        }
    }
    // single loan detail
    public function singleLoanDetail($mono_loan_calculation_id)
    {
        try {
            $singleDocument = LoanApplication::where('mono_loan_calculation', $mono_loan_calculation_id)->latest()->first();

            // dd($singleDocument);
            $data = [

                'Loan Amount' => $singleDocument->loan_amount,
                'Repayment duration' => $singleDocument->repayment_duration
            ];
            return ResponseHelper::success($data, "single Loan Detail");
        } catch (Exception $ex) {
            Log::error('Error in LoanApplicationController@singleBeneficiary: ' . $ex->getMessage());
            return ResponseHelper::error('do not show single Loan applications');
        }
    }
}
