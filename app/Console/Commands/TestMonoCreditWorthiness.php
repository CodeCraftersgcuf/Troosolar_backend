<?php

namespace App\Console\Commands;

use App\Services\MonoService;
use Illuminate\Console\Command;
use Throwable;

class TestMonoCreditWorthiness extends Command
{
    protected $signature = 'mono:test-credit
        {accountId : Mono linked account id}
        {bvn : Customer BVN (11 characters)}
        {--principal=30000000 : Principal in kobo}
        {--term=12 : Loan term in months}
        {--rate=5 : Interest rate percent for Mono}
        {--no-bureau : Set run_credit_check to false}';

    protected $description = 'Test Mono Credit Worthiness POST for a linked account (uses MONO_SECRET_KEY from .env)';

    public function handle(MonoService $monoService): int
    {
        $accountId = trim((string) $this->argument('accountId'));
        $bvn = preg_replace('/\s+/', '', trim((string) $this->argument('bvn')));

        if (strlen($bvn) !== 11) {
            $this->error('BVN must be exactly 11 characters.');

            return self::FAILURE;
        }

        $this->info('Checking Mono API credentials...');
        $auth = $monoService->verifyApiCredentials();
        $this->line($auth['ok'] ? '✓ Secret key valid' : '✗ ' . $auth['message']);

        if (! $auth['ok']) {
            return self::FAILURE;
        }

        try {
            $this->info("Fetching account details for {$accountId}...");
            $details = $monoService->getAccountDetails($accountId);
            $name = $details['data']['name'] ?? $details['data']['account']['name'] ?? 'unknown';
            $this->line('✓ Account reachable: ' . $name);
        } catch (Throwable $e) {
            $this->warn('Account details failed: ' . $e->getMessage());
        }

        $params = [
            'bvn' => $bvn,
            'principal' => (int) $this->option('principal'),
            'interest_rate' => (float) $this->option('rate'),
            'term' => (int) $this->option('term'),
            'run_credit_check' => ! $this->option('no-bureau'),
        ];

        $this->info('POST /v2/accounts/{id}/creditworthiness');
        $this->table(['Field', 'Value'], collect($params)->map(fn ($v, $k) => [$k, $v])->values()->all());

        try {
            $monoService->initiateCreditWorthiness($accountId, $params);
            $this->newLine();
            $this->info('✓ Credit worthiness request accepted by Mono.');
            $this->line('Results will arrive via webhook: mono.events.account_credit_worthiness');
            $this->line('Check admin → Mono Loans → Credit Sessions in a few moments.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('✗ Mono credit worthiness failed:');
            $this->line($e->getMessage());

            return self::FAILURE;
        }
    }
}
