<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanRepayment extends Model
{
    use HasFactory;

       protected $fillable = [
        'amount',
        'status',
        'user_id',
        'mono_calculation_id',
    ];

    // relations
    // user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // monoLoanCalculation
    public function monoLoanCalculation()
    {
        return $this->belongsTo(MonoLoanCalculation::class, 'mono_calculation_id');
    }
}
