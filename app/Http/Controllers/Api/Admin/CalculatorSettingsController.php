<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\CalculatorSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CalculatorSettingsController extends Controller
{
    public function show()
    {
        try {
            $defaults = CalculatorSetting::defaults();
            $settings = CalculatorSetting::firstOrCreate(
                ['is_active' => true],
                $defaults
            );

            return ResponseHelper::success([
                'inverter_ranges' => $settings->inverter_ranges ?: $defaults['inverter_ranges'],
                'solar_savings_profiles' => $settings->solar_savings_profiles ?: $defaults['solar_savings_profiles'],
                'bundle_types' => $settings->bundle_types ?: $defaults['bundle_types'],
                'solar_maintenance_5_years' => (float) ($settings->solar_maintenance_5_years ?? $defaults['solar_maintenance_5_years']),
            ], 'Calculator settings retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve calculator settings', 500);
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'inverter_ranges' => 'nullable|array|min:1',
            'inverter_ranges.*.min_kw' => 'required_with:inverter_ranges|numeric|min:0',
            'inverter_ranges.*.max_kw' => 'required_with:inverter_ranges|numeric|min:0',
            'inverter_ranges.*.target_kva' => 'required_with:inverter_ranges|numeric|min:0',
            'inverter_ranges.*.label' => 'required_with:inverter_ranges|string|max:255',

            'solar_savings_profiles' => 'nullable|array|min:1',
            'solar_savings_profiles.*.key' => 'required_with:solar_savings_profiles|string|max:80',
            'solar_savings_profiles.*.label' => 'required_with:solar_savings_profiles|string|max:120',
            'solar_savings_profiles.*.hourly_fuel_l' => 'required_with:solar_savings_profiles|numeric|min:0',
            'solar_savings_profiles.*.default_monthly_service' => 'required_with:solar_savings_profiles|numeric|min:0',
            'solar_savings_profiles.*.default_monthly_phcn' => 'required_with:solar_savings_profiles|numeric|min:0',
            'solar_savings_profiles.*.default_cost_of_generator' => 'required_with:solar_savings_profiles|numeric|min:0',
            'solar_savings_profiles.*.cost_of_solar_system' => 'required_with:solar_savings_profiles|numeric|min:0',
            'solar_savings_profiles.*.fuel_cost_per_litre' => 'nullable|numeric|min:0',

            'bundle_types' => 'nullable|array|min:1',
            'bundle_types.*' => 'required_with:bundle_types|string|max:255',

            'solar_maintenance_5_years' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return ResponseHelper::error($validator->errors()->first(), 422);
        }

        try {
            $defaults = CalculatorSetting::defaults();
            $settings = CalculatorSetting::firstOrCreate(
                ['is_active' => true],
                $defaults
            );

            $payload = [];
            if ($request->has('inverter_ranges')) {
                $payload['inverter_ranges'] = array_values($request->input('inverter_ranges'));
            }
            if ($request->has('solar_savings_profiles')) {
                $payload['solar_savings_profiles'] = array_values($request->input('solar_savings_profiles'));
            }
            if ($request->has('bundle_types')) {
                $payload['bundle_types'] = collect($request->input('bundle_types'))
                    ->map(fn ($v) => is_string($v) ? trim($v) : '')
                    ->filter(fn ($v) => $v !== '')
                    ->unique()
                    ->values()
                    ->all();
            }
            if ($request->has('solar_maintenance_5_years')) {
                $payload['solar_maintenance_5_years'] = (float) $request->input('solar_maintenance_5_years');
            }

            $settings->update($payload);

            return ResponseHelper::success([
                'inverter_ranges' => $settings->inverter_ranges ?: $defaults['inverter_ranges'],
                'solar_savings_profiles' => $settings->solar_savings_profiles ?: $defaults['solar_savings_profiles'],
                'bundle_types' => $settings->bundle_types ?: $defaults['bundle_types'],
                'solar_maintenance_5_years' => (float) ($settings->solar_maintenance_5_years ?? $defaults['solar_maintenance_5_years']),
            ], 'Calculator settings updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to update calculator settings', 500);
        }
    }
}

