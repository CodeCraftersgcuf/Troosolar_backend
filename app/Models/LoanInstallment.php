<?php

// app/Models/LoanInstallment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class LoanInstallment extends Model
{
    protected $guarded = [];
    protected $casts = [
        'payment_date' => 'datetime',
        'paid_at'      => 'datetime',
        'amount'       => 'decimal:2',
    ];

    // Optional: centralize allowed statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID    = 'paid';
    public const STATUS_OVERDUE = 'overdue'; // stored or computed (see below)

    /* -------- Scopes -------- */

    /** installments for a specific month (uses payment_date, not created_at) */
    public function scopeForMonth(Builder $q, Carbon $day): Builder
    {
        return $q->whereBetween('payment_date', [
            $day->copy()->startOfMonth(),
            $day->copy()->endOfMonth(),
        ]);
    }

    /** due on or before a date, still pending */
    public function scopeDueBy(Builder $q, Carbon $day): Builder
    {
        return $q->where('status', self::STATUS_PENDING)
                 ->whereDate('payment_date', '<=', $day->toDateString());
    }

    /** strictly overdue right now (pending + payment_date < today) */
    public function scopeOverdue(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PENDING)
                 ->whereDate('payment_date', '<', now()->toDateString());
    }

    /** next upcoming pending installment (today or later) */
    public function scopeNextPending(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PENDING)
                 ->whereDate('payment_date', '>=', now()->toDateString())
                 ->orderBy('payment_date', 'asc');
    }

    /* -------- Accessors -------- */

    /**
     * If you prefer not to *store* "overdue" and compute it on the fly:
     * - keep status as 'pending' or 'paid' in DB,
     * - expose a computed_status attribute.
     */
    public function getComputedStatusAttribute(): string
    {
        if ($this->status === self::STATUS_PAID) {
            return self::STATUS_PAID;
        }
        if ($this->status === self::STATUS_PENDING && $this->payment_date->lt(now()->startOfDay())) {
            return self::STATUS_OVERDUE;
        }
        return self::STATUS_PENDING;
    }
}
