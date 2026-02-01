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
    'mono_loan_calculation',
    'loan_amount',
    'repayment_duration',
    'customer_type',
    'product_category',
    'audit_type',
    'property_state',
    'property_address',
    'property_landmark',
    'property_floors',
    'property_rooms',
    'is_gated_estate',
    'estate_name',
    'estate_address',
    'credit_check_method',
    'bank_statement_path',
    'live_photo_path',
    'social_media_handle',
    'guarantor_id',
    'admin_notes',
    'counter_offer_min_deposit',
    'counter_offer_min_tenor',
    'order_items_snapshot',
];

    protected $casts = [
        'order_items_snapshot' => 'array',
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

 public function guarantor()
 {
     return $this->hasOne(Guarantor::class, 'loan_application_id');
 }

}