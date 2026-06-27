<?php

namespace App\Services;

use App\Models\LoanInstallment;
use App\Models\LoanRepayment;
use App\Models\Transaction;

/**
 * Shared logic to mark installments paid (wallet, Flutterwave, Mono Direct Debit).
 */
class InstallmentPaymentRecorder
{
    public function markPaidFromMonoDebit(LoanInstallment $installment, string $reference, float $amount): LoanInstallment
    {
        $inst = LoanInstallment::where('id', $installment->id)->lockForUpdate()->firstOrFail();

        if ($inst->status === LoanInstallment::STATUS_PAID) {
            return $inst;
        }

        $transaction = Transaction::create([
            'title' => 'BNPL installment (Mono Direct Debit)',
            'amount' => $amount,
            'status' => 'success',
            'type' => 'debit',
            'method' => 'mono_direct_debit',
            'transacted_at' => now(),
            'user_id' => $inst->user_id,
            'tx_id' => $reference,
            'reference' => 'INSTALLMENT#' . $inst->id,
        ]);

        $inst->update([
            'status' => LoanInstallment::STATUS_PAID,
            'paid_at' => now(),
            'transaction_id' => $transaction->id,
        ]);

        LoanRepayment::create([
            'amount' => $amount,
            'user_id' => $inst->user_id,
            'mono_calculation_id' => $inst->mono_calculation_id,
        ]);

        return $inst->fresh();
    }
}
