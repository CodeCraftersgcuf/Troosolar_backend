<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'amount',
        'bank_name',
        'account_name',
        'account_number',
        'status',
        'tx_id',
        'reference',
        'processed_at',
        'transaction_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
