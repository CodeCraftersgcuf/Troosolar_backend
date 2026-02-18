<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BundleMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'bundle_id',
        'material_id',
        'quantity',
        'rate_override',
    ];

    protected $casts = [
        'quantity'      => 'decimal:2',
        'rate_override' => 'decimal:2',
    ];

    /**
     * Get the bundle that owns this material
     */
    public function bundle()
    {
        return $this->belongsTo(Bundles::class, 'bundle_id');
    }

    /**
     * Get the material
     */
    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }
}
