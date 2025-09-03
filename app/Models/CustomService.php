<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomService extends Model
{
    use HasFactory;

    protected $fillable = ['bundle_id', 'title', 'service_amount'];

// App\Models\CustomService.php

public function bundle()
{
    return $this->belongsTo(Bundles::class);
}


}

