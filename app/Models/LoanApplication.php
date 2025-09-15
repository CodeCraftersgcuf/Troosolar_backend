<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanApplication extends Model
{
    use HasFactory;
    protected $fillable = [
    'title_document',
    'upload_document',
    'beneficiary_name',
    'beneficiary_email',
    'beneficiary_relationship',
    'beneficiary_phone',
    'status',
    'user_id',
    'mono_loan_calculation'
];

// loan history
public function loanHistories()
{
    return $this->hasMany(LoanHistory::class);
}
public function loanCalculation()
{
    return $this->hasOne(LoanCalculation::class, 'user_id', 'user_id');
}

public function loanStatus()
{
    return $this->hasOne(LoanStatus::class, 'loan_application_id');
}
 public function loan_installments()
 {
     return $this->hasMany(LoanInstallment::class, 'mono_calculation_id', 'mono_loan_calculation');
 }
 public function mono(){
     return $this->hasOne(MonoLoanCalculation::class, 'id', 'mono_loan_calculation');
 }

 public function user()
 {
     return $this->belongsTo(User::class);
 }


}