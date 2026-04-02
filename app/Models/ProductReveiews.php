<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReveiews extends Model
{
    use HasFactory;
    protected $fillable = [
        'product_id',
        'user_id',
        'review',
        'rating',
        'admin_reply',
        'admin_replied_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'admin_replied_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}