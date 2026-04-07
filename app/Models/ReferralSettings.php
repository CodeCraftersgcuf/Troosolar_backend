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
        'outright_discount_percentage',
        'referral_reward_type',
        'referral_reward_value',
        'referral_fixed_ngn',
    ];

    protected $casts = [
        'commission_percentage' => 'decimal:2',
        'minimum_withdrawal' => 'decimal:2',
        'outright_discount_percentage' => 'decimal:2',
        'referral_reward_value' => 'decimal:2',
        'referral_fixed_ngn' => 'decimal:2',
    ];

    /**
     * Get the current referral settings (singleton pattern)
     */
    public static function getSettings()
    {
        return static::first() ?? static::create([
            'commission_percentage' => 5.00,
            'minimum_withdrawal' => 0.00,
            'outright_discount_percentage' => 0.00,
            'referral_reward_type' => 'fixed',
            'referral_reward_value' => 50000.00,
            'referral_fixed_ngn' => 50000.00,
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
