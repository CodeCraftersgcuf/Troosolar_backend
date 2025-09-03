<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CartItem extends Model
{
    protected $fillable = [
        'user_id',
        'itemable_type',
        'itemable_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $appends = ['type'];

    protected $hidden = ['itemable_type'];

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    // Custom attribute to return type as 'product' or 'bundle'
    public function getTypeAttribute()
    {
        if ($this->itemable_type === \App\Models\Product::class) {
            return 'product';
        } elseif ($this->itemable_type === \App\Models\Bundles::class) {
            return 'bundle';
        }
        return null;
    }
}