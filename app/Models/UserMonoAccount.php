<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMonoAccount extends Model
{
    protected $fillable = [
        'user_id',
        'mono_account_id',
        'mono_customer_id',
        'mono_dd_customer_id',
        'bank_name',
        'account_name',
        'account_number_last4',
        'status',
        'linked_at',
    ];

    protected $casts = [
        'linked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isLinked(): bool
    {
        return $this->status === 'linked' && $this->mono_account_id !== '';
    }

    public function displayLabel(): string
    {
        if ($this->bank_name) {
            $suffix = $this->account_number_last4 ? ' ••••' . $this->account_number_last4 : '';

            return $this->bank_name . $suffix;
        }

        return 'Bank account connected';
    }
}
