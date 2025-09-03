<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bundles extends Model
{
    protected $fillable = [
        'title',
        'total_price',
        'discount_price',
        'discount_end_date',
        'featured_image',
        'bundle_type',
    ];

public function bundleItems()
{
    return $this->hasMany(BundleItems::class, 'bundle_id');
}


public function customServices()
{
    return $this->hasMany(CustomService::class, 'bundle_id');
}



}
