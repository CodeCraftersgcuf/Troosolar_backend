<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundleCustomAppliance extends Model
{
    protected $fillable = [
        'bundle_id',
        'name',
        'wattage',
        'quantity',
        'estimated_daily_hours_usage',
    ];

    protected $casts = [
        'wattage' => 'decimal:2',
        'quantity' => 'integer',
        'estimated_daily_hours_usage' => 'decimal:2',
    ];

    public function bundle()
    {
        return $this->belongsTo(Bundles::class, 'bundle_id');
    }
}
