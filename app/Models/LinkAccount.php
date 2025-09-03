<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkAccount extends Model
{
    use HasFactory;

      protected $fillable = [
        'account_number',
        'account_name',
        'bank_name',
        'status',
        'user_id',
    ];
}