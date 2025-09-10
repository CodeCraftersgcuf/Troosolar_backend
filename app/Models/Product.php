<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // ✅ use this

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category_id',
        'brand_id',
        'price',
        'discount_price',
        'discount_end_date',
        'stock',
        'installation_price',
        'top_deal',
        'installation_compulsory',
        'featured_image',
        'old_quantity',
        // ❌ This line does nothing in $fillable and also conflicts with relation name:
        // 'images' => 'array',
    ];

    protected $casts = [
        'discount_end_date' => 'date',
        'top_deal' => 'boolean',
        'installation_compulsory' => 'boolean',
        'price' => 'double',
        'discount_price' => 'double',
        'installation_price' => 'double',
        // If you keep a JSON column named `images`, cast it here instead:
        // 'images' => 'array',
    ];
    protected $appends = ['featured_image_url'];


    /**
     * Relationship: Product belongs to a Category
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relationship: Product belongs to a Brand
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function images()
    {
        return $this->hasMany(\App\Models\ProductImage::class);
    }

    public function details()
    {
        return $this->hasMany(\App\Models\ProductDetail::class);
    }

    // loan calculation relationship
    public function loanCalculations()
    {
        return $this->belongsToMany(LoanCalculation::class, 'loan_calculation_product');
    }
    public function reviews()
    {
        return $this->hasMany(ProductReveiews::class);
    }
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (!$this->featured_image) return null;
        if (Str::startsWith($this->featured_image, ['http://', 'https://', '/storage/'])) {
            return $this->featured_image;
        }
        return Storage::url($this->featured_image);
    }

    // ...rest unchanged

}
