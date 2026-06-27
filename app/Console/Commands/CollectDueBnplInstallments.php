<?php

namespace App\Console\Commands;

use App\Models\LoanInstallment;
use App\Services\MonoDirectDebitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CollectDueBnplInstallments extends Command
{
    protected $signature = 'bnpl:collect-due-installments {--dry-run : List due installments without debiting}';

    protected $description = 'Debit due BNPL installments via Mono Direct Debit mandates';

    public function handle(MonoDirectDebitService $directDebitService): int
    {
        $due = LoanInstallment::query()
            ->where('status', LoanInstallment::STATUS_PENDING)
            ->whereDate('payment_date', '<=', now()->toDateString())
            ->orderBy('payment_date')
            ->get();

        if ($due->isEmpty()) {
            $this->info('No due installments.');

            return self::SUCCESS;
        }

        $this->info('Found ' . $due->count() . ' due installment(s).');

        foreach ($due as $installment) {
            $label = "Installment #{$installment->id} user={$installment->user_id} amount={$installment->amount}";

            if ($this->option('dry-run')) {
                $this->line("[dry-run] {$label}");
                continue;
            }

            try {
                $directDebitService->collectInstallment($installment);
                $this->info("Debited {$label}");
            } catch (\Throwable $e) {
                $this->warn("Skipped {$label}: {$e->getMessage()}");
                Log::warning('BNPL auto-debit skipped', [
                    'installment_id' => $installment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }
}
