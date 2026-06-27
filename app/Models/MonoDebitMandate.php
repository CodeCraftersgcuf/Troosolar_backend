<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonoDebitMandate extends Model
{
    public const STATUS_PENDING = 'pending_authorization';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_READY = 'ready';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'mono_calculation_id',
        'loan_application_id',
        'mono_mandate_id',
        'mono_customer_id',
        'mono_account_id',
        'reference',
        'status',
        'approved',
        'ready_to_debit',
        'authorization_url',
        'amount_kobo',
        'debit_type',
        'start_date',
        'end_date',
        'meta',
        'approved_at',
        'ready_at',
    ];

    protected $casts = [
        'approved' => 'boolean',
        'ready_to_debit' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'meta' => 'array',
        'approved_at' => 'datetime',
        'ready_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function monoCalculation(): BelongsTo
    {
        return $this->belongsTo(MonoLoanCalculation::class, 'mono_calculation_id');
    }

    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }

    public function debitTransactions(): HasMany
    {
        return $this->hasMany(MonoDebitTransaction::class);
    }

    public function canDebit(): bool
    {
        return $this->ready_to_debit
            && in_array($this->status, [self::STATUS_READY, self::STATUS_ACTIVE, self::STATUS_APPROVED], true)
            && $this->mono_mandate_id;
    }
}
