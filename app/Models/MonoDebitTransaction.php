<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonoDebitTransaction extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_SUCCESSFUL = 'successful';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'mono_debit_mandate_id',
        'loan_installment_id',
        'reference',
        'amount_kobo',
        'status',
        'mono_response',
        'error_message',
    ];

    protected $casts = [
        'mono_response' => 'array',
    ];

    public function mandate(): BelongsTo
    {
        return $this->belongsTo(MonoDebitMandate::class, 'mono_debit_mandate_id');
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(LoanInstallment::class, 'loan_installment_id');
    }
}
