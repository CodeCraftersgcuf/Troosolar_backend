<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanDistribute extends Model
{
    use HasFactory;
    protected $fillable = [
        'distribute_amount',
        'status',
        'reject_reason',
        'loan_application_id',
    ];
}
