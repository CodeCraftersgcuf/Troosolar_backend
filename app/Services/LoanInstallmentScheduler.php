<?php

namespace App\Services;

use App\Models\LoanInstallment;
use App\Models\MonoLoanCalculation; // assumes you have this model
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LoanInstallmentScheduler
{
    /**
     * Generate equal monthly installments for a MonoLoanCalculation.
     *
     * @param  int             $monoCalcId                 MonoLoanCalculation primary key
     * @param  Carbon|null     $firstPaymentDate           If null, uses linked LoanCalculation->repayment_date or now()->addMonth()
     * @param  bool            $forceRegenerate            If true, deletes existing PENDING installments first
     * @return array{created:int, total_amount:string}
     *
     * @throws \Throwable
     */
    public static function generate(int $monoCalcId, ?Carbon $firstPaymentDate = null, bool $forceRegenerate = false): array
    {
        $mono = MonoLoanCalculation::with('loanCalculation')->findOrFail($monoCalcId);
        $calc = $mono->loanCalculation; // must exist

        $userId     = $calc->user_id;
        $duration   = (int) $calc->repayment_duration;
        $perMonth   = (float) $calc->monthly_payment;

        if ($duration <= 0) {
            throw new \InvalidArgumentException('repayment_duration must be > 0');
        }
        if ($perMonth <= 0) {
            throw new \InvalidArgumentException('monthly_payment must be > 0');
        }

        // Decide first due date
        if ($firstPaymentDate instanceof Carbon) {
            $due0 = $firstPaymentDate->copy();
        } elseif (!empty($calc->repayment_date)) {
            $due0 = Carbon::parse($calc->repayment_date);
        } else {
            $due0 = now()->addMonth(); // default behavior you already use
        }

        return DB::transaction(function () use ($monoCalcId, $userId, $duration, $perMonth, $due0, $forceRegenerate) {

            if ($forceRegenerate) {
                LoanInstallment::where('mono_calculation_id', $monoCalcId)
                    ->where('status', 'pending')
                    ->delete();
            } else {
                $exists = LoanInstallment::where('mono_calculation_id', $monoCalcId)->exists();
                if ($exists) {
                    return [
                        'created' => 0,
                        'total_amount' => number_format($perMonth * $duration, 2, '.', ''),
                    ];
                }
            }

            $amounts = [];
            for ($i = 1; $i <= $duration; $i++) {
                $amounts[] = round($perMonth, 2); // base equal split
            }

            // Adjust last installment to match exact target sum
            $targetTotal = round($perMonth * $duration, 2);
            $currentSum  = round(array_sum($amounts), 2);
            $drift       = round($targetTotal - $currentSum, 2);
            $amounts[$duration - 1] = round($amounts[$duration - 1] + $drift, 2);

            $created = 0;
            foreach ($amounts as $i => $amount) {
                $dueDate = $due0->copy()->addMonths($i)->startOfDay(); // month 1..N

                LoanInstallment::create([
                    'user_id'            => $userId,
                    'status'             => 'pending',            // your existing enum/values
                    'mono_calculation_id' => $monoCalcId,         // **keep your name**
                    'amount'             => $amount,
                    'remaining_duration' => $duration - ($i + 1), // 0 at last month
                    'payment_date'       => $dueDate,
                ]);
                $created++;
            }

            return [
                'created' => $created,
                'total_amount' => number_format($targetTotal, 2, '.', ''),
            ];
        });
    }
}
