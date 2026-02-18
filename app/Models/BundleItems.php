<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundleItems extends Model
{
    use HasFactory;

    protected $fillable = ['bundle_id', 'product_id', 'quantity', 'rate_override'];

    protected $casts = [
        'quantity'      => 'integer',
        'rate_override' => 'decimal:2',
    ];

  public function customServices()
{
    return $this->hasMany(CustomService::class, 'bundle_items');
}

public function product()
{
    return $this->belongsTo(Product::class, 'product_id');
}

}

