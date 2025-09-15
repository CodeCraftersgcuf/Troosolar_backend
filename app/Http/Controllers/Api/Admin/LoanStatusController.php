<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\InterestPercentage;
use App\Models\LoanApplication;
use App\Models\LoanCalculation;
use App\Models\LoanDistribute;
use App\Models\LoanInstallment;
use App\Models\LoanStatus;
use App\Models\MonoLoanCalculation;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoanStatusController extends Controller
{
    // show all loan status
public function allLoansStatus()
{
    try {
        $allLoans = LoanStatus::with('loan_application')->get();

        $totalLoans = LoanApplication::count();
        $loanSend = LoanStatus::where('send_status', 'active')->count();
        $loanApproved = LoanStatus::where('approval_status', 'active')->count();

        $loans = [
            'total loans' => $totalLoans,
            'loan send' => $loanSend,
            'loan approved' => $loanApproved
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
                'name' => $loan->loan_application->beneficiary_name,
                'Amount' => $loan->loan_application->loan_amount,
                'date' => $loan->loan_application->created_at->format('Y-m-d'),
                'send status' => $loan->send_status,
                'approval status' => $loan->approval_status
            ];
        }

        $merged = array_merge($loans, $data);
        return ResponseHelper::success($merged, 'All Loan Status');
    } catch (Exception $e) {
        return ResponseHelper::error('Failed to retrieve loan statuses', 500);
    }
}

    // fullLoanDetails - Get all loans for a specific user
public function fullLoanDetails(string $id)
{
    try {
        // 1) User + wallet
        $user = User::with('wallet')->find($id);
        if (!$user) {
            return ResponseHelper::error('User not found', 404);
        }

        // 2) Pull ALL user loan-calculations
        $calculations = LoanCalculation::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        if ($calculations->isEmpty()) {
            // keep the same top-level format
            return ResponseHelper::success([
                'user_info' => [
                    'id'    => $user->id,
                    'name'  => trim(($user->first_name ?? '').' '.($user->sur_name ?? '')),
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'wallet_info' => [
                    'loan_balance'   => (float)($user->wallet->loan_balance ?? 0),
                    'shop_balance'   => (float)($user->wallet->shop_balance ?? 0),
                    'wallet_status'  => $user->wallet->status ?? 'inactive',
                ],
                'total_loans' => 0,
                'loans'       => [],
            ], 'No loans found for this user');
        }

        // 3) Batch fetch linked monos and applications
        $calcIds = $calculations->pluck('id')->all();

        $monos = MonoLoanCalculation::whereIn('loan_calculation_id', $calcIds)->get();

        // Map mono by calc_id
        $monoByCalcId = $monos->keyBy('loan_calculation_id');

        // Applications are stored with foreign key string 'mono_loan_calculation' (the MonoLoanCalculation id)
        $monoIds = $monos->pluck('id')->all();

        $applications = LoanApplication::where('user_id', $user->id)
            ->whereIn('mono_loan_calculation', $monoIds)
            ->get();

        // Map application by mono_id
        $appByMonoId = $applications->keyBy('mono_loan_calculation');

        // 4) Interest — prefer persisted on the records; latest Interest only as fallback
        $latestInterest = InterestPercentage::latest()->first();

        $loansOut = [];

        foreach ($calculations as $calc) {
            $mono = $monoByCalcId->get($calc->id);
            $app  = $mono ? $appByMonoId->get((string)$mono->id) : null;

            // Prefer values from Mono (offer) if present, else fall back to Calculation
            $loanAmount         = (float)($mono->loan_amount ?? $calc->loan_amount ?? 0);
            $repaymentDuration  = (int)  ($mono->repayment_duration ?? $calc->repayment_duration ?? 0);
            $interestRate       = (float)(
                $mono->interest_rate
                ?? $calc->interest_percentage
                ?? ($latestInterest ? $latestInterest->interest_percentage : 0)
            );

            // Beneficiary data exists only after application
            $beneficiaryName         = $app->beneficiary_name   ?? null;
            $beneficiaryEmail        = $app->beneficiary_email  ?? null;
            $beneficiaryPhone        = $app->beneficiary_phone  ?? null;
            $beneficiaryRelationship = $app->beneficiary_relationship ?? null;

            // For backward compatibility with your previous response
            // (you had a `loan_limit`; not present in these models)
            $loanLimit = $app->loan_limit ?? null;

            // Pipeline status synthesized from 3 models (no loan_status table):
            // We also attach milestone dates (created_at) so FE can render progress.
            $loanStatusSynth = [
                'calculation' => [
                    'status' => (string)($calc->status ?? 'calculated'), // you save 'calculated' / 'pending' / 'approved' / 'submitted' / 'offered'
                    'date'   => optional($calc->created_at)->toDateTimeString(),
                ],
                'offer' => $mono ? [
                    'status' => (string)($mono->status ?? 'offered'),    // usually 'pending'|'approved'|'offered'
                    'date'   => optional($mono->created_at)->toDateTimeString(),
                ] : null,
                'application' => $app ? [
                    'status' => (string)($app->status ?? 'submitted'),   // e.g. 'submitted'|'approved'
                    'date'   => optional($app->created_at)->toDateTimeString(),
                ] : null,
                // Grant (allocation) — inferred:
                // If mono + calc (and optionally application) are marked 'approved', consider granted.
                'grant' => (($mono && ($mono->status === 'approved')) ||
                            ($calc && ($calc->status === 'approved')) ||
                            ($app && ($app->status === 'approved')))
                    ? [
                        'status' => 'approved',
                        'date'   => optional(($app ?? $mono ?? $calc)->updated_at)->toDateTimeString(),
                    ]
                    : null,
            ];

            $loansOut[] = [
                // keep previous keys where possible
                'loan_application_id'     => $app->id ?? null,
                'user_id'                 => $user->id,
                'user_name'               => trim(($user->first_name ?? '').' '.($user->sur_name ?? '')),
                'beneficiary_name'        => $beneficiaryName,
                'beneficiary_email'       => $beneficiaryEmail,
                'beneficiary_phone'       => $beneficiaryPhone,
                'beneficiary_relationship'=> $beneficiaryRelationship,

                'loan_limit'              => $loanLimit,
                'loan_amount'             => $loanAmount,
                'repayment_duration'      => $repaymentDuration,
                'interest_rate'           => $interestRate,

                'financing_partner'       => 'allied bank', // unchanged literal from your old response
                // Best “application_status” we can expose without loan_status table:
                // prefer the most progressed stage
                'application_status'      => $app->status
                    ?? ($mono->status ?? $calc->status ?? 'calculated'),

                'created_at'              => ($app->created_at ?? $mono->created_at ?? $calc->created_at),
                'updated_at'              => ($app->updated_at ?? $mono->updated_at ?? $calc->updated_at),

                // Replaces old `loan_status` object (from LoanStatus model) with synthesized pipeline
                'loan_status'             => $loanStatusSynth,

                // Optional: include ids so FE can deep-link screens
                'ids' => [
                    'loan_calculation_id'   => $calc->id,
                    'mono_loan_calculation' => $mono->id ?? null,
                    'loan_application_id'   => $app->id ?? null,
                ],
            ];
        }

        return ResponseHelper::success([
            'user_info' => [
                'id'    => $user->id,
                'name'  => trim(($user->first_name ?? '').' '.($user->sur_name ?? '')),
                'email' => $user->email,
                'phone' => $user->phone,
            ],
            'wallet_info' => [
                'loan_balance'  => (float)($user->wallet->loan_balance ?? 0),
                'shop_balance'  => (float)($user->wallet->shop_balance ?? 0),
                'wallet_status' => $user->wallet->status ?? 'inactive',
            ],
            'total_loans' => count($loansOut),
            'loans'       => $loansOut,
        ], 'All Loan Details Retrieved Successfully');

    } catch (\Throwable $e) {
        Log::error('Error retrieving loan details: '.$e->getMessage());
        return ResponseHelper::error('Failed to retrieve loan details', 500);
    }
}


}