<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'icon',
        'has_method_selection',
    ];

    // Optional: Define relationship with brands
    public function brands()
    {
        return $this->hasMany(Brand::class);
    }

    // Optional: Define relationship with products (if needed)
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
