<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use function PHPSTORM_META\map;

class Brand extends Model
{
    use HasFactory;
    protected $fillable=[
        'title',
        'icon',
        'category_id'
    ];
    public function category(){
        return $this->belongsTo(Category::class);
    }


    public function products()
{
    return $this->hasMany(\App\Models\Product::class);
}

}
