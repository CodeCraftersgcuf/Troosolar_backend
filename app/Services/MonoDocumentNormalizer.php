<?php

namespace App\Services;

class MonoDocumentNormalizer
{
    /**
     * @param  array<string, mixed>  $documents
     * @return array<string, mixed>
     */
    public function normalize(array $documents): array
    {
        $account = $this->extractAccount($documents['account_details'] ?? null);
        $identity = $this->extractIdentity($documents['identity'] ?? null);
        $balance = $this->extractBalance($documents['balance'] ?? null);
        $statement = $this->extractStatement($documents['statement_json'] ?? null);

        $linked = is_array($documents['linked_account'] ?? null)
            ? $documents['linked_account']
            : [];

        $accountNumber = $account['account_number'] ?? null;
        $lastFour = $linked['account_number_last4'] ?? null;
        if (! $lastFour && is_string($accountNumber) && strlen($accountNumber) >= 4) {
            $lastFour = substr($accountNumber, -4);
        }

        $balanceKobo = $balance['balance_kobo'] ?? $account['balance_kobo'] ?? null;

        $summary = [
            'bank_name' => $this->firstNonEmpty(
                $linked['bank_name'] ?? null,
                $account['bank_name'] ?? null
            ),
            'account_name' => $this->firstNonEmpty(
                $linked['account_name'] ?? null,
                $account['account_name'] ?? null,
                $identity['full_name'] ?? null
            ),
            'account_number' => $accountNumber,
            'account_number_last4' => $lastFour,
            'account_type' => $account['account_type'] ?? null,
            'currency' => $this->firstNonEmpty(
                $account['currency'] ?? null,
                $balance['currency'] ?? null,
                'NGN'
            ),
            'balance_kobo' => $balanceKobo,
            'balance_naira' => $balanceKobo !== null ? round((float) $balanceKobo / 100, 2) : null,
            'linked_at' => $linked['linked_at'] ?? null,
            'mono_account_id' => $documents['mono_account_id'] ?? null,
        ];

        $creditSession = $documents['latest_credit_session'] ?? null;
        $formattedCredit = null;
        if (is_array($creditSession)) {
            $formattedCredit = [
                'status' => $creditSession['status'] ?? null,
                'can_afford' => $creditSession['can_afford'] ?? null,
                'principal_naira' => $creditSession['principal_naira'] ?? null,
                'monthly_payment_naira' => $creditSession['monthly_payment_naira'] ?? null,
                'total_debt_naira' => $creditSession['total_debt_naira'] ?? null,
                'bvn' => $creditSession['bvn'] ?? null,
                'created_at' => $creditSession['created_at'] ?? null,
                'error_message' => $creditSession['error_message'] ?? null,
            ];
        }

        return [
            'user_id' => $documents['user_id'] ?? null,
            'summary' => $summary,
            'identity' => $identity,
            'transactions' => $statement['transactions'],
            'statement_meta' => $statement['meta'],
            'latest_credit_session' => $formattedCredit,
            'partial_errors' => is_array($documents['partial_errors'] ?? null)
                ? $documents['partial_errors']
                : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractAccount(mixed $payload): array
    {
        $inner = $this->unwrap($payload);
        $account = is_array($inner['account'] ?? null) ? $inner['account'] : $inner;

        $institution = is_array($account['institution'] ?? null) ? $account['institution'] : [];

        $balanceRaw = $account['balance'] ?? $account['available_balance'] ?? null;

        return [
            'account_name' => $this->firstNonEmpty(
                $account['name'] ?? null,
                $account['account_name'] ?? null,
                $account['accountName'] ?? null
            ),
            'account_number' => $this->firstNonEmpty(
                $account['account_number'] ?? null,
                $account['accountNumber'] ?? null
            ),
            'bank_name' => $this->firstNonEmpty(
                $institution['name'] ?? null,
                $account['bank_name'] ?? null,
                $account['bankName'] ?? null
            ),
            'account_type' => $this->firstNonEmpty(
                $account['type'] ?? null,
                $account['account_type'] ?? null
            ),
            'currency' => $account['currency'] ?? null,
            'balance_kobo' => $this->toKobo($balanceRaw),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function extractIdentity(mixed $payload): array
    {
        $inner = $this->unwrap($payload);

        return [
            'full_name' => $this->firstNonEmpty(
                $inner['full_name'] ?? null,
                $inner['fullName'] ?? null,
                $inner['name'] ?? null
            ),
            'email' => $inner['email'] ?? null,
            'phone' => $this->firstNonEmpty(
                $inner['phone'] ?? null,
                $inner['phone_number'] ?? null,
                $inner['phoneNumber'] ?? null
            ),
            'bvn' => $inner['bvn'] ?? null,
            'dob' => $this->firstNonEmpty(
                $inner['dob'] ?? null,
                $inner['date_of_birth'] ?? null,
                $inner['dateOfBirth'] ?? null
            ),
            'gender' => $inner['gender'] ?? null,
            'address' => $this->firstNonEmpty(
                $inner['address'] ?? null,
                is_array($inner['address'] ?? null) ? json_encode($inner['address']) : null
            ),
        ];
    }

    /**
     * @return array{balance_kobo: int|null, currency: string|null}
     */
    private function extractBalance(mixed $payload): array
    {
        $inner = $this->unwrap($payload);
        $amount = $inner['balance']
            ?? $inner['available_balance']
            ?? $inner['availableBalance']
            ?? null;

        return [
            'balance_kobo' => $this->toKobo($amount),
            'currency' => $inner['currency'] ?? null,
        ];
    }

    /**
     * @return array{transactions: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    private function extractStatement(mixed $payload): array
    {
        if (! is_array($payload)) {
            return ['transactions' => [], 'meta' => []];
        }

        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $inner = $payload;

        if (isset($payload['data']) && is_array($payload['data'])) {
            if (array_is_list($payload['data'])) {
                return [
                    'transactions' => $this->normalizeTransactions($payload['data']),
                    'meta' => $meta,
                ];
            }
            $inner = $payload['data'];
            if (is_array($inner['meta'] ?? null)) {
                $meta = array_merge($meta, $inner['meta']);
            }
        }

        $list = null;
        foreach (['statement', 'transactions', 'data'] as $key) {
            if (isset($inner[$key]) && is_array($inner[$key]) && array_is_list($inner[$key])) {
                $list = $inner[$key];
                break;
            }
        }

        if ($list === null && array_is_list($inner)) {
            $list = $inner;
        }

        return [
            'transactions' => $this->normalizeTransactions($list ?? []),
            'meta' => $meta,
        ];
    }

    /**
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function normalizeTransactions(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'id' => $row['id'] ?? $row['_id'] ?? null,
                'date' => $row['date'] ?? $row['transaction_date'] ?? $row['created_at'] ?? null,
                'narration' => $this->firstNonEmpty(
                    $row['narration'] ?? null,
                    $row['description'] ?? null,
                    $row['narrative'] ?? null
                ),
                'type' => $row['type'] ?? null,
                'amount_kobo' => $this->toKobo($row['amount'] ?? null),
                'amount_naira' => ($k = $this->toKobo($row['amount'] ?? null)) !== null
                    ? round($k / 100, 2)
                    : null,
                'balance_kobo' => $this->toKobo($row['balance'] ?? null),
                'category' => $row['category'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function unwrap(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [];
        }

        $current = $payload;
        for ($i = 0; $i < 4; $i++) {
            if (! isset($current['data']) || ! is_array($current['data'])) {
                break;
            }
            if (array_is_list($current['data'])) {
                return $current;
            }
            $current = $current['data'];
        }

        return $current;
    }

    private function toKobo(mixed $amount): ?int
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        return (int) round((float) $amount);
    }

    private function firstNonEmpty(mixed ...$values): mixed
    {
        foreach ($values as $value) {
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }
}
