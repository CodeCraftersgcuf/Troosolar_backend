<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'insurance_fee_percentage',
        'residual_fee_percentage',
        'equity_contribution_min',
        'equity_contribution_max',
        'interest_rate_min',
        'interest_rate_max',
        'repayment_tenor_min',
        'repayment_tenor_max',
        'management_fee_percentage',
        'minimum_loan_amount',
        'is_active',
    ];

    protected $casts = [
        'insurance_fee_percentage' => 'decimal:2',
        'residual_fee_percentage' => 'decimal:2',
        'equity_contribution_min' => 'decimal:2',
        'equity_contribution_max' => 'decimal:2',
        'interest_rate_min' => 'decimal:2',
        'interest_rate_max' => 'decimal:2',
        'management_fee_percentage' => 'decimal:2',
        'minimum_loan_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'repayment_tenor_min' => 'integer',
        'repayment_tenor_max' => 'integer',
    ];
}
