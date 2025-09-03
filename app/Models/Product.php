<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
   'images' => 'array',
];

 protected $casts = [
     

        'discount_end_date' => 'date',
        'top_deal' => 'boolean',
    'installation_compulsory' => 'boolean',
        'price' => 'double',
        'discount_price' => 'double',
        'installation_price' => 'double',
    ];

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

}