<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductDetail extends Model
{
    use HasFactory;
    protected $fillable = ['detail', 'product_id', 'status'];

public function product()
{
    return $this->belongsTo(\App\Models\Product::class);
}


}
