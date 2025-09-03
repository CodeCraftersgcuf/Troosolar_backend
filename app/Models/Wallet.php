<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'loan_balance',
        'status',
        'shop_balance'
        ];
        public function user(){
            return $this->belongsTo(User::class);
        }
    public function transactions()
{
    return $this->hasMany(\App\Models\Transaction::class, 'user_id', 'user_id');
}

}