<?php

namespace App\Http\Controllers;

use App\Models\LoanCalculation;
use App\Models\LoanInstallment;
use App\Models\MonoLoanCalculation;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InstallmentController extends Controller
{
    public function historyWithCurrentMonth()
{
    $user = Auth::user();
    $now  = now();

    // Define "overdue": not paid AND payment_date strictly before today (at start of day)
    $overdueBase = LoanInstallment::query()
        ->where('user_id', $user->id)
        ->where('status', '!=', LoanInstallment::STATUS_PAID)
        ->whereDate('payment_date', '<', $now->copy()->startOfDay());

    $overdueCount  = (clone $overdueBase)->count();
    $overdueAmount = (clone $overdueBase)->sum('amount'); // adjust if you track partial payments

    // Current month list (annotated with is_overdue)
    $current = LoanInstallment::query()
        ->where('user_id', $user->id)
        ->forMonth($now)
        ->orderBy('payment_date', 'asc')
        ->get()
        ->map(function ($i) use ($now) {
            $mapped = $this->mapInstallment($i);
            $isOverdue = $i->status !== LoanInstallment::STATUS_PAID
                && $i->payment_date?->lt($now->copy()->startOfDay());
            // Add is_overdue without touching your styling/shape from mapInstallment
            return array_merge($mapped, ['is_overdue' => $isOverdue]);
        });

    // History list (all except current month; also annotated with is_overdue)
    $history = LoanInstallment::query()
        ->where('user_id', $user->id)
        ->where(function ($q) use ($now) {
            $q->whereDate('payment_date', '<', $now->copy()->startOfMonth())
              ->orWhereDate('payment_date', '>', $now->copy()->endOfMonth());
        })
        ->orderBy('payment_date', 'desc')
        ->get()
        ->map(function ($i) use ($now) {
            $mapped = $this->mapInstallment($i);
            $isOverdue = $i->status !== LoanInstallment::STATUS_PAID
                && $i->payment_date?->lt($now->copy()->startOfDay());
            return array_merge($mapped, ['is_overdue' => $isOverdue]);
        });

    // Activity/completion flags you already had
    $isActive     = LoanInstallment::where('status', LoanInstallment::STATUS_PAID)
        ->where('user_id', $user->id)
        ->exists();

    $hasUnpaid    = LoanInstallment::where('status', '!=', LoanInstallment::STATUS_PAID)
        ->where('user_id', $user->id)
        ->exists();
        $loanCalculation = LoanCalculation::where('user_id', $user->id)->latest()->first();
        $monoLoanCalculation=MonoLoanCalculation::where('loan_calculation_id', $loanCalculation->id)->latest()->first();

    return response()->json([
        'status' => 'success',
        'data'   => [
            'current_month'  => $current,
            'history'        => $history,
            'isActive'       => $isActive,
            'isCompleted'    => !$hasUnpaid,
            'hasOverdue'     => $overdueCount > 0,
            'overdueCount'   => $overdueCount,
            'overdueAmount'  => (float) $overdueAmount, // cast for clean JSON
            'loan'=>$monoLoanCalculation
        ],
    ]);
}


    /**
     * Pay a single installment.
     * - method = 'wallet' → type = 'shop' | 'loan' required → deduct from respective balance
     * - method != 'wallet' → require tx_id from FE
     */
    public function payInstallment(Request $request, int $installmentId)
    {
        $request->validate([
            'method' => 'required|string|max:50',         // e.g., wallet|bank|card|transfer
            'type'   => 'nullable|string|in:shop,loan',   // required if method=wallet
            'tx_id'  => 'nullable|string|max:255',        // required if method!=wallet
            'reference' => 'nullable|string|max:255',
            'title'     => 'nullable|string|max:255',
            // optional override amount? normally we use installment->amount
        ]);

        $user = Auth::user();

        return DB::transaction(function () use ($user, $request, $installmentId) {
            // Lock installment row for safe concurrent ops
            $inst = LoanInstallment::where('id', $installmentId)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($inst->status === LoanInstallment::STATUS_PAID) {
                return response()->json([
                    'status'  => 'success',
                    'message' => 'Installment already paid',
                    'data'    => $this->mapInstallment($inst),
                ]);
            }

            $amount   = (float) $inst->amount;
            $method   = $request->string('method')->toString();
            $type     = $request->string('type')->toString();  // shop|loan for wallet
            $title    = $request->string('title')->toString() ?: 'Loan installment payment';
            $reference= $request->string('reference')->toString() ?: 'INSTALLMENT#'.$inst->id;

            $txPayload = [
                'title'         => $title,
                'amount'        => $amount,
                'status'        => 'success',   // or 'pending' if async
                'type'          => 'debit',     // money going out of user
                'method'        => $method,
                'transacted_at' => now(),
                'user_id'       => $user->id,
                'tx_id'         => null,        // filled below
                'reference'     => $reference,
            ];

            if ($method === 'wallet') {
                // Require a type (shop or loan)
                if (!in_array($type, ['shop','loan'], true)) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'type is required and must be one of: shop, loan when method=wallet',
                    ], 422);
                }

                // Lock wallet row
                $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();
                if (!$wallet) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Wallet not found',
                    ], 404);
                }

                // Check and deduct from the chosen balance
                if ($type === 'shop') {
                    if ((float)$wallet->shop_balance < $amount) {
                        return response()->json([
                            'status'  => 'error',
                            'message' => 'Insufficient shop balance',
                        ], 422);
                    }
                    $wallet->shop_balance = (float)$wallet->shop_balance - $amount;
                    $txPayload['tx_id']   = 'WALLET-SHOP-'.now()->timestamp.'-'.$inst->id;
                } else { // loan
                    if ((float)$wallet->loan_balance < $amount) {
                        return response()->json([
                            'status'  => 'error',
                            'message' => 'Insufficient loan balance',
                        ], 422);
                    }
                    $wallet->loan_balance = (float)$wallet->loan_balance - $amount;
                    $txPayload['tx_id']   = 'WALLET-LOAN-'.now()->timestamp.'-'.$inst->id;
                }

                $wallet->save();

            } else {
                // Non-wallet method → need tx_id from FE
                $txId = $request->string('tx_id')->toString();
                if (!$txId) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'tx_id is required for non-wallet methods',
                    ], 422);
                }
                $txPayload['tx_id'] = $txId;
            }

            // Create a Transaction and link it
            $transaction = Transaction::create($txPayload);

            // Mark installment as paid & link transaction
            $inst->update([
                'status'         => LoanInstallment::STATUS_PAID,
                'paid_at'        => now(),
                'transaction_id' => $transaction->id,
            ]);

            // Return fresh
            $inst->load('transaction');

            return response()->json([
                'status'  => 'success',
                'message' => 'Installment paid successfully',
                'data'    => $this->mapInstallment($inst),
            ]);
        });
    }

    /** Map installment for FE */
    private function mapInstallment(LoanInstallment $i): array
    {
        return [
            'id'                  => $i->id,
            'mono_calculation_id' => $i->mono_calculation_id,
            'amount'              => (float) $i->amount,
            'payment_date'        => optional($i->payment_date)?->toDateString(),
            'status'              => $i->status,
            'computed_status'     => $i->computed_status,
            'paid_at'             => optional($i->paid_at)?->toDateTimeString(),
            'remaining_duration'  => $i->remaining_duration,
            'transaction'         => $i->transaction ? [
                'id'         => $i->transaction->id,
                'tx_id'      => $i->transaction->tx_id,
                'method'     => $i->transaction->method,
                'type'       => $i->transaction->type,
                'status'     => $i->transaction->status,
                'amount'     => (float) $i->transaction->amount,
                'reference'  => $i->transaction->reference,
                'transacted_at' => optional($i->transaction->transacted_at)?->toDateTimeString(),
            ] : null,
        ];
    }
}
