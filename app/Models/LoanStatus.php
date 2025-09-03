<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanStatus extends Model
{
    use HasFactory;
    protected $fillable = [
        'send_status',
        'send_date',
        'approval_status',
        'approval_date',
        'disbursement_status',
        'disbursement_date',
        'loan_application_id',
    ];

public function loan_application()
{
    return $this->belongsTo(LoanApplication::class, 'loan_application_id');
}



}
