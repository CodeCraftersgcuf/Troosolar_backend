<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonoLoanCalculation extends Model
{
    use HasFactory;
    protected $fillable = [
        'down_payment',
        'loan_calculation_id',
        'credit_score',
        'transaction',
        'loan_amount',
        'repayment_duration',
        'status',
        'loan_application_id',
        'interest_rate',
        'total_amount',
    ];
    // relations

    //  loan calculation
    public function loanCalculation()
    {
        return $this->belongsTo(LoanCalculation::class);
    }

    // loan installment (table column is mono_calculation_id, not mono_loan_calculation_id)
    public function loanInstallments()
    {
        return $this->hasMany(LoanInstallment::class, 'mono_calculation_id');
    }

    // loan repayment
    public function loanRepayments()
    {
        return $this->hasMany(LoanRepayment::class, 'mono_calculation_id');
    }

    protected $appends = ['is_overdue'];

    public function getIsOverdueAttribute()
    {
        return now()->gt($this->due_date) && $this->status !== 'paid';
    }
    public function order()
    {
        return $this->hasOne(Order::class, 'mono_calculation_id');
    }
}
