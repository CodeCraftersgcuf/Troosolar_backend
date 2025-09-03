<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanHistory extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'loan_application_id'];

    // relation with user
    public function user()
{
    return $this->belongsTo(User::class);
}

// loan application
public function loanApplication()
{
    return $this->belongsTo(LoanApplication::class);
}

}