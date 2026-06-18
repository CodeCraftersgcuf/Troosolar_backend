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
    public function getAccountDetails(string $accountId): array
    {
        return $this->request('GET', '/v2/accounts/' . $accountId);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccountIdentity(string $accountId): array
    {
        return $this->request('GET', '/v2/accounts/' . $accountId . '/identity');
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccountBalance(string $accountId): array
    {
        return $this->request('GET', '/v2/accounts/' . $accountId . '/balance');
    }

    /**
     * @return array<string, mixed>
     */
    public function getAccountStatement(string $accountId, string $period = 'last6months', string $output = 'json'): array
    {
        return $this->request('GET', '/v2/accounts/' . $accountId . '/statement', [], [
            'period' => $period,
            'output' => $output,
            'format' => 'v2',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function pollStatementPdfJob(string $accountId, string $jobId): array
    {
        return $this->request('GET', '/v2/accounts/' . $accountId . '/statement/jobs/' . $jobId);
    }

    /**
     * Request PDF statement and poll until ready (max attempts).
     *
     * @return array{job_id: string|null, status: string, download_url: string|null, raw: array<string, mixed>}
     */
    public function fetchStatementPdfUrl(string $accountId, string $period = 'last6months', int $maxAttempts = 12): array
    {
        $init = $this->getAccountStatement($accountId, $period, 'pdf');
        $jobId = $init['data']['id'] ?? $init['data']['jobId'] ?? $init['jobId'] ?? null;

        if (! is_string($jobId) || $jobId === '') {
            $directPath = $init['data']['path'] ?? null;
            if (is_string($directPath) && $directPath !== '') {
                return [
                    'job_id' => null,
                    'status' => 'BUILT',
                    'download_url' => $directPath,
                    'raw' => $init,
                ];
            }

            return [
                'job_id' => null,
                'status' => 'unknown',
                'download_url' => null,
                'raw' => $init,
            ];
        }

        for ($i = 0; $i < $maxAttempts; $i++) {
            if ($i > 0) {
                usleep(500000);
            }

            $poll = $this->pollStatementPdfJob($accountId, $jobId);
            $status = strtoupper((string) ($poll['data']['status'] ?? ''));
            $path = $poll['data']['path'] ?? null;

            if ($status === 'BUILT' && is_string($path) && $path !== '') {
                return [
                    'job_id' => $jobId,
                    'status' => $status,
                    'download_url' => $path,
                    'raw' => $poll,
                ];
            }

            if (in_array($status, ['FAILED', 'ERROR'], true)) {
                return [
                    'job_id' => $jobId,
                    'status' => $status,
                    'download_url' => null,
                    'raw' => $poll,
                ];
            }
        }

        return [
            'job_id' => $jobId,
            'status' => 'processing',
            'download_url' => null,
            'raw' => $init,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $body = [], array $query = []): array
    {
        $secret = (string) config('services.mono.secret_key', '');
        if ($secret === '') {
            throw new RuntimeException('Mono secret key is not configured.');
        }

        $url = $this->baseUrl . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        try {
            $client = Http::withHeaders([
                'accept' => 'application/json',
                'content-type' => 'application/json',
                'mono-sec-key' => $secret,
            ])->timeout(60);

            $response = $method === 'POST'
                ? $client->post($url, $body)
                : $client->get($url);
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
