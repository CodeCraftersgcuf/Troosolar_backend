<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteBanner extends Model
{
    protected $fillable = ['key', 'path'];

    public const KEY_HOME_PROMO = 'home_promotion';
}
