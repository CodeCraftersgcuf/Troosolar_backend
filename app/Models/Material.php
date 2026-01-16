<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_category_id',
        'name',
        'unit',
        'warranty',
        'rate',
        'selling_rate',
        'profit',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'warranty' => 'integer',
        'rate' => 'decimal:2',
        'selling_rate' => 'decimal:2',
        'profit' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the category that owns this material
     */
    public function category()
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }
}
