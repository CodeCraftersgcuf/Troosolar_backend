<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonoWebhookEvent extends Model
{
    protected $fillable = [
        'event',
        'mono_account_id',
        'payload_hash',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
