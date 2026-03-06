<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalculatorSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'inverter_ranges',
        'solar_savings_profiles',
        'bundle_types',
        'solar_maintenance_5_years',
        'is_active',
    ];

    protected $casts = [
        'inverter_ranges' => 'array',
        'solar_savings_profiles' => 'array',
        'bundle_types' => 'array',
        'solar_maintenance_5_years' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public static function defaults(): array
    {
        return [
            'inverter_ranges' => [
                ['min_kw' => 0.0, 'max_kw' => 0.96, 'target_kva' => 1.2, 'label' => '1.2kVA/12V'],
                ['min_kw' => 0.96, 'max_kw' => 1.13, 'target_kva' => 1.5, 'label' => '1.5kVA/12V'],
                ['min_kw' => 1.13, 'max_kw' => 1.35, 'target_kva' => 1.8, 'label' => '1.8kVA/12V'],
                ['min_kw' => 1.35, 'max_kw' => 2.88, 'target_kva' => 3.6, 'label' => '3.6kVA/24V or 4kVA/24V'],
                ['min_kw' => 2.88, 'max_kw' => 4.0, 'target_kva' => 5.0, 'label' => '5kVA/48V'],
                ['min_kw' => 4.0, 'max_kw' => 4.5, 'target_kva' => 6.0, 'label' => '6kVA/48V or 6.5kVA/48V'],
                ['min_kw' => 4.5, 'max_kw' => 6.0, 'target_kva' => 8.0, 'label' => '8kVA/48V'],
                ['min_kw' => 6.0, 'max_kw' => 7.5, 'target_kva' => 10.0, 'label' => '10kVA - 2 units of 5kVA/48V'],
                ['min_kw' => 7.5, 'max_kw' => 9.0, 'target_kva' => 12.0, 'label' => '12kVA/48V'],
                ['min_kw' => 9.0, 'max_kw' => 11.5, 'target_kva' => 15.0, 'label' => '15kVA - 3 units of 5kVA/48V'],
                ['min_kw' => 11.5, 'max_kw' => 13.5, 'target_kva' => 18.0, 'label' => '18kVA - 3 units of 6kVA/48V'],
                ['min_kw' => 13.5, 'max_kw' => 15.0, 'target_kva' => 20.0, 'label' => '20kVA - 4 units of 5kVA/48V'],
            ],
            'solar_savings_profiles' => [
                ['key' => '1.2kva', 'label' => '1.2kVA', 'hourly_fuel_l' => 0.65, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 130000, 'cost_of_solar_system' => 0, 'fuel_cost_per_litre' => 750],
                ['key' => '1.5kva', 'label' => '1.5kVA', 'hourly_fuel_l' => 0.65, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 130000, 'cost_of_solar_system' => 0, 'fuel_cost_per_litre' => 750],
                ['key' => '1.8kva', 'label' => '1.8kVA', 'hourly_fuel_l' => 0.65, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 130000, 'cost_of_solar_system' => 0, 'fuel_cost_per_litre' => 750],
                ['key' => '3.6kva', 'label' => '3.6kVA', 'hourly_fuel_l' => 1.5, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 0, 'cost_of_solar_system' => 2600000, 'fuel_cost_per_litre' => 750],
                ['key' => '4kva', 'label' => '4kVA', 'hourly_fuel_l' => 1.5, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 0, 'cost_of_solar_system' => 2600000, 'fuel_cost_per_litre' => 750],
                ['key' => '5kva', 'label' => '5kVA', 'hourly_fuel_l' => 3.0, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 0, 'cost_of_solar_system' => 4500000, 'fuel_cost_per_litre' => 750],
                ['key' => '6kva', 'label' => '6kVA', 'hourly_fuel_l' => 3.0, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 0, 'cost_of_solar_system' => 4500000, 'fuel_cost_per_litre' => 750],
                ['key' => '6.5kva', 'label' => '6.5kVA', 'hourly_fuel_l' => 3.0, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 0, 'cost_of_solar_system' => 4500000, 'fuel_cost_per_litre' => 750],
                ['key' => '7.5kva', 'label' => '7.5kVA', 'hourly_fuel_l' => 4.75, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 0, 'cost_of_solar_system' => 7500000, 'fuel_cost_per_litre' => 750],
                ['key' => '8kva', 'label' => '8kVA', 'hourly_fuel_l' => 4.75, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 0, 'cost_of_solar_system' => 7500000, 'fuel_cost_per_litre' => 750],
                ['key' => '8.5kva', 'label' => '8.5kVA', 'hourly_fuel_l' => 4.75, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 0, 'cost_of_solar_system' => 7500000, 'fuel_cost_per_litre' => 750],
                ['key' => '10kva', 'label' => '10kVA', 'hourly_fuel_l' => 6.25, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 0, 'cost_of_solar_system' => 11000000, 'fuel_cost_per_litre' => 750],
                ['key' => '11kva', 'label' => '11kVA', 'hourly_fuel_l' => 6.25, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 0, 'cost_of_solar_system' => 11000000, 'fuel_cost_per_litre' => 750],
                ['key' => '12kva-diesel', 'label' => '12kVA - Diesel', 'hourly_fuel_l' => 2.9, 'default_monthly_service' => 20000, 'default_monthly_phcn' => 5000, 'default_cost_of_generator' => 0, 'cost_of_solar_system' => 14000000, 'fuel_cost_per_litre' => 1000],
            ],
            'bundle_types' => [
                'Inverter + Battery',
                'Solar+Inverter+Battery',
            ],
            'solar_maintenance_5_years' => 150000,
        ];
    }
}

