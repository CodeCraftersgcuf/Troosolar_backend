<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MonoService
{
    private string $baseUrl = 'https://api.withmono.com';

    public function getPublicKey(): string
    {
        return (string) config('services.mono.public_key', '');
    }

    public function getEnv(): string
    {
        return (string) config('services.mono.env', 'sandbox');
    }

    public function getWebhookSecret(): string
    {
        return (string) config('services.mono.webhook_secret', '');
    }

    public function shouldRunCreditCheck(): bool
    {
        return (bool) config('services.mono.run_credit_check', true);
    }

    /**
     * Exchange temporary Connect code for permanent account id.
     *
     * @throws RuntimeException
     */
    public function exchangeCode(string $code): string
    {
        $response = $this->request('POST', '/v2/accounts/auth', ['code' => $code]);

        $accountId = $response['id'] ?? $response['data']['id'] ?? null;
        if (! is_string($accountId) || $accountId === '') {
            throw new RuntimeException('Mono auth response did not include account id.');
        }

        return $accountId;
    }

    /**
     * Initiate async creditworthiness analysis.
     *
     * @param  array{bvn: string, principal: int, interest_rate: float|int, term: int, run_credit_check: bool}  $params
     *
     * @throws RuntimeException
     */
    public function initiateCreditWorthiness(string $accountId, array $params): void
    {
        $body = [
            'bvn' => $params['bvn'],
            'principal' => (int) $params['principal'],
            'interest_rate' => (float) $params['interest_rate'],
            'term' => (int) $params['term'],
            'run_credit_check' => (bool) ($params['run_credit_check'] ?? true),
        ];

        $this->request('POST', '/v2/accounts/' . $accountId . '/creditworthiness', $body);
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $body = []): array
    {
        $secret = (string) config('services.mono.secret_key', '');
        if ($secret === '') {
            throw new RuntimeException('Mono secret key is not configured.');
        }

        try {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'mono-sec-key' => $secret,
            ])->timeout(60);

            $response = $method === 'POST'
                ? $response->post($this->baseUrl . $path, $body)
                : $response->get($this->baseUrl . $path, $body);
        } catch (RequestException $e) {
            $message = $e->response?->json('message') ?? $e->getMessage();
            throw new RuntimeException('Mono API request failed: ' . $message, 0, $e);
        }

        if (! $response->successful()) {
            $message = $response->json('message') ?? $response->body();
            throw new RuntimeException('Mono API error: ' . $message);
        }

        return $response->json() ?? [];
    }
}
