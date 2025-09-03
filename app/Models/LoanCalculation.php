<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanCalculation extends Model
{
    use HasFactory;
    protected $fillable = [
        'loan_amount',
        'repayment_duration',
        'status',
        'user_id',
        'product_id',
        'repayment_date',
        'product_amount',
        'monthly_payment',
        'interest_percentage'
    ];

    protected $casts = [
    'monthly_payment' => 'decimal:2',
];
      public function user()
    {
        return $this->belongsTo(User::class);
    }


public function monoLoanCalculation()
{
    return $this->hasOne(MonoLoanCalculation::class, 'loan_calculation_id');
}

public function loanDistributed()
{
    return $this->hasOne(LoanDistributed::class, 'loan_calculation_id');
}
public function loanApplication()
{
    return $this->belongsTo(LoanApplication::class, 'user_id', 'user_id');
}

}