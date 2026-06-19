<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonoCreditCheckSession extends Model
{
    protected $fillable = [
        'user_id',
        'mono_account_id',
        'mono_customer_id',
        'bvn',
        'principal_kobo',
        'interest_rate',
        'term_months',
        'run_credit_check',
        'api_request_payload',
        'api_init_response',
        'status',
        'can_afford',
        'monthly_payment_kobo',
        'credit_worthiness_payload',
        'error_message',
        'loan_application_id',
    ];

    protected $casts = [
        'principal_kobo' => 'integer',
        'interest_rate' => 'decimal:2',
        'term_months' => 'integer',
        'run_credit_check' => 'boolean',
        'api_request_payload' => 'array',
        'api_init_response' => 'array',
        'can_afford' => 'boolean',
        'monthly_payment_kobo' => 'integer',
        'credit_worthiness_payload' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function syncToLoanApplication(?LoanApplication $application = null): void
    {
        $app = $application ?? $this->loanApplication;
        if (! $app) {
            return;
        }

        $app->update([
            'mono_account_id' => $this->mono_account_id,
            'mono_customer_id' => $this->mono_customer_id,
            'mono_credit_status' => $this->status,
            'mono_can_afford' => $this->can_afford,
            'mono_monthly_payment_kobo' => $this->monthly_payment_kobo,
            'mono_credit_report' => $this->credit_worthiness_payload,
            'mono_credit_session_id' => $this->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function resolvedApiRequestPayload(): array
    {
        if (is_array($this->api_request_payload) && $this->api_request_payload !== []) {
            return $this->api_request_payload;
        }

        return [
            'endpoint' => 'https://api.withmono.com/v2/accounts/' . $this->mono_account_id . '/creditworthiness',
            'method' => 'POST',
            'body' => [
                'bvn' => $this->bvn,
                'principal' => (int) $this->principal_kobo,
                'interest_rate' => (float) $this->interest_rate,
                'term' => (int) $this->term_months,
                'run_credit_check' => (bool) $this->run_credit_check,
            ],
            'note' => 'Reconstructed from session fields — run a new credit check to store the exact POST audit.',
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function resolvedApiInitResponse(): ?array
    {
        if (is_array($this->api_init_response) && $this->api_init_response !== []) {
            return $this->api_init_response;
        }

        return null;
    }
}
