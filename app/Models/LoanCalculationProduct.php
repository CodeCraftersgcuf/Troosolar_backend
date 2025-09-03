<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanCalculationProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'loan_calculation_id'
    ];

}
