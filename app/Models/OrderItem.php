<?php

// app/Models/OrderItem.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id','itemable_type','itemable_id','quantity','unit_price','subtotal',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function itemable(): MorphTo
    {
        // Eager-load nested relations only for specific morph types
        return $this->morphTo()->morphWith([
            \App\Models\Product::class => [], // nothing extra to load
            \App\Models\Bundles::class => ['bundleItems.product'], // need product for fallback image
        ]);
    }
}