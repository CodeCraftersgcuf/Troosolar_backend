<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'audit_type',
        'customer_type',
        'property_state',
        'property_address',
        'property_landmark',
        'property_floors',
        'property_rooms',
        'is_gated_estate',
        'estate_name',
        'estate_address',
        'status',
        'admin_notes',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'is_gated_estate' => 'boolean',
        'property_floors' => 'integer',
        'property_rooms' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
