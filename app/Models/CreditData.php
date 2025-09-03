<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditData extends Model
{
    use HasFactory;
    protected $fillable = [
        'total_income',
        'monthly_income',
    ];
}
