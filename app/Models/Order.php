<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
     protected $fillable = [
        'user_id',
        'delivery_address_id',
        'order_number',
        'total_price',
        'payment_method',
        'payment_status',
        'order_status',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryAddress()
    {
        return $this->belongsTo(DeliveryAddress::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function monoCalculation() {
    return $this->belongsTo(MonoLoanCalculation::class, 'mono_calculation_id');
}
}