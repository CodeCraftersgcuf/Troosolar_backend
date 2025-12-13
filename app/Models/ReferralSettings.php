<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferralSettings extends Model
{
    use HasFactory;

    protected $table = 'referral_settings';

    protected $fillable = [
        'commission_percentage',
        'minimum_withdrawal',
    ];

    protected $casts = [
        'commission_percentage' => 'decimal:2',
        'minimum_withdrawal' => 'decimal:2',
    ];

    /**
     * Get the current referral settings (singleton pattern)
     */
    public static function getSettings()
    {
        return static::first() ?? static::create([
            'commission_percentage' => 0.00,
            'minimum_withdrawal' => 0.00,
        ]);
    }

    /**
     * Update referral settings
     */
    public static function updateSettings(array $data)
    {
        $settings = static::getSettings();
        $settings->update($data);
        return $settings;
    }
}
