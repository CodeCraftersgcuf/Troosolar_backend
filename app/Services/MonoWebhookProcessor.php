<?php

namespace App\Services;

use App\Models\MonoCreditCheckSession;
use App\Models\UserMonoAccount;
use App\Services\MonoDirectDebitService;
use App\Models\MonoWebhookEvent;
use Illuminate\Support\Facades\Log;

class MonoWebhookProcessor
{
    public function __construct(
        private readonly MonoDirectDebitService $directDebitService
    ) {}

    public function verifySecret(?string $headerSecret): bool
    {
        $expected = (string) config('services.mono.webhook_secret', '');
        if ($expected === '') {
            return false;
        }

        return hash_equals($expected, (string) $headerSecret);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function process(array $payload): void
    {
        $event = (string) ($payload['event'] ?? '');
        $data = $payload['data'] ?? [];
        if (! is_array($data)) {
            $data = [];
        }

        $accountId = (string) ($data['id'] ?? $data['account'] ?? '');
        $payloadHash = hash('sha256', json_encode($payload));

        if ($this->isDuplicate($event, $accountId, $payloadHash)) {
            return;
        }

        $webhookEvent = MonoWebhookEvent::create([
            'event' => $event,
            'mono_account_id' => $accountId !== '' ? $accountId : null,
            'payload_hash' => $payloadHash,
            'payload' => $payload,
        ]);

        try {
            if (str_starts_with($event, 'events.mandates.') || str_contains($event, 'mandates.')) {
                $this->directDebitService->handleMandateWebhook($event, $data);
            } else {
                match ($event) {
                    'mono.events.account_connected' => $this->handleAccountConnected($data),
                    'mono.events.account_credit_worthiness' => $this->handleCreditWorthiness($data),
                    default => Log::info('Mono webhook ignored event', ['event' => $event]),
                };
            }

            $webhookEvent->update(['processed_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('Mono webhook processing failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleAccountConnected(array $data): void
    {
        $accountId = (string) ($data['id'] ?? '');
        $customerId = isset($data['customer']) ? (string) $data['customer'] : null;

        if ($accountId === '') {
            return;
        }

        MonoCreditCheckSession::where('mono_account_id', $accountId)->update([
            'mono_customer_id' => $customerId,
        ]);

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $institution = is_array($data['institution'] ?? null) ? $data['institution'] : [];
        $bankName = (string) ($institution['name'] ?? $meta['bank_name'] ?? $data['bank_name'] ?? '');
        $accountName = (string) ($data['account_name'] ?? $meta['account_name'] ?? '');
        $accountNumber = (string) ($data['account_number'] ?? $meta['account_number'] ?? '');
        $lastFour = strlen($accountNumber) >= 4 ? substr($accountNumber, -4) : null;

        $updates = array_filter([
            'mono_customer_id' => $customerId,
            'bank_name' => $bankName !== '' ? $bankName : null,
            'account_name' => $accountName !== '' ? $accountName : null,
            'account_number_last4' => $lastFour,
        ], fn ($v) => $v !== null);

        if ($updates !== []) {
            UserMonoAccount::where('mono_account_id', $accountId)->update($updates);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleCreditWorthiness(array $data): void
    {
        $accountId = (string) ($data['account'] ?? '');
        if ($accountId === '') {
            return;
        }

        $session = MonoCreditCheckSession::where('mono_account_id', $accountId)
            ->orderByDesc('id')
            ->first();

        if (! $session) {
            Log::warning('Mono credit worthiness webhook: no session for account', ['account' => $accountId]);

            return;
        }

        if (isset($data['message']) && ! isset($data['summary'])) {
            $session->update([
                'status' => 'failed',
                'error_message' => (string) $data['message'],
                'credit_worthiness_payload' => $data,
            ]);
            $session->syncToLoanApplication();

            return;
        }

        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $canAfford = array_key_exists('can_afford', $summary) ? (bool) $summary['can_afford'] : null;
        $monthlyPayment = isset($summary['monthly_payment']) ? (int) $summary['monthly_payment'] : null;

        $session->update([
            'status' => 'completed',
            'can_afford' => $canAfford,
            'monthly_payment_kobo' => $monthlyPayment,
            'credit_worthiness_payload' => $data,
            'error_message' => null,
        ]);

        $session->syncToLoanApplication();
    }

    private function isDuplicate(string $event, string $accountId, string $payloadHash): bool
    {
        return MonoWebhookEvent::where('event', $event)
            ->where('mono_account_id', $accountId !== '' ? $accountId : null)
            ->where('payload_hash', $payloadHash)
            ->exists();
    }
}
