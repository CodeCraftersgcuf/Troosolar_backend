<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanInstallment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'status',
        'mono_calculation_id',
        'amount',
        'remaining_duration',
    ];

      public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function monoLoanCalculation()
    {
        return $this->belongsTo(MonoLoanCalculation::class, 'mono_calculation_id');
    }

    public function loan_application()
    {
        return $this->belongsTo(LoanApplication::class, 'mono_calculation_id', 'mono_loan_calculation_id');
    }
}
