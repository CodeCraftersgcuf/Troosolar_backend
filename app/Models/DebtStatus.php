<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebtStatus extends Model
{
    use HasFactory;
    protected $fillable = [
        'debt_status',
        'total_owned',
        'account_statement'
    ];
}
