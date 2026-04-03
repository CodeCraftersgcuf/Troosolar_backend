<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckoutSetting extends Model
{
    protected $table = 'checkout_settings';

    protected $fillable = [
        'delivery_fee',
        'delivery_min_working_days',
        'delivery_max_working_days',
        'insurance_fee',
        'installation_schedule_working_days',
        'installation_description',
    ];

    protected $casts = [
        'delivery_fee' => 'integer',
        'delivery_min_working_days' => 'integer',
        'delivery_max_working_days' => 'integer',
        'insurance_fee' => 'integer',
        'installation_schedule_working_days' => 'integer',
    ];

    /**
     * Singleton row for shop checkout (delivery fee, estimates, installation copy).
     */
    public static function get(): self
    {
        $row = self::query()->first();
        if (! $row) {
            $row = self::create([
                'delivery_fee' => (int) config('checkout.delivery_fee', 2000),
                'delivery_min_working_days' => (int) config('checkout.delivery_min_working_days', 7),
                'delivery_max_working_days' => (int) config('checkout.delivery_max_working_days', 10),
                'insurance_fee' => (int) config('checkout.insurance_fee', 0),
                'installation_schedule_working_days' => (int) config('checkout.installation_schedule_working_days', 7),
                'installation_description' => (string) config('checkout.installation_text', ''),
            ]);
        }

        return $row;
    }
}
