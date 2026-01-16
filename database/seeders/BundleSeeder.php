<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bundles;

class BundleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if bundles already exist
        if (Bundles::where('bundle_type', 'Inverter + Battery')->count() > 0 || 
            Bundles::where('bundle_type', 'Solar+Inverter+Battery')->count() > 0) {
            $this->command->info('Bundles already exist. Skipping...');
            return;
        }

        // Inverter + Battery Bundles
        $inverterBatteryBundles = [
            [
                'title' => 'Y1.2kVA+1.3kWh',
                'bundle_type' => 'Inverter + Battery',
                'total_price' => 877366.63,
                'discount_price' => 0.00,
                'inver_rating' => '1.2 kVA',
                'total_output' => '1.3 kWh',
                'total_load' => null,
            ],
            [
                'title' => 'Y1.2kVA+2.5kWh',
                'bundle_type' => 'Inverter + Battery',
                'total_price' => 1072385.06,
                'discount_price' => 0.00,
                'inver_rating' => '1.2 kVA',
                'total_output' => '2.5 kWh',
                'total_load' => null,
            ],
            [
                'title' => 'Y1.2kVA+3.8kWh',
                'bundle_type' => 'Inverter + Battery',
                'total_price' => 1308817.88,
                'discount_price' => 0.00,
                'inver_rating' => '1.2 kVA',
                'total_output' => '3.8 kWh',
                'total_load' => null,
            ],
        ];

        // Solar+Inverter+Battery Bundles
        $solarInverterBatteryBundles = [
            [
                'title' => 'Y0.6kWp+1.2kVA+1.3kW',
                'bundle_type' => 'Solar+Inverter+Battery',
                'total_price' => 1097728.19,
                'discount_price' => 0.00,
                'inver_rating' => '1.2 kVA',
                'total_output' => '1.3 kWh',
                'total_load' => '600 W',
            ],
            [
                'title' => 'Y1.2kWp+1.2kVA+2.5kW',
                'bundle_type' => 'Solar+Inverter+Battery',
                'total_price' => 1445732.56,
                'discount_price' => 0.00,
                'inver_rating' => '1.2 kVA',
                'total_output' => '2.5 kWh',
                'total_load' => '1200 W',
            ],
            [
                'title' => 'Y1.2kWp+1.2kVA+3.8kW',
                'bundle_type' => 'Solar+Inverter+Battery',
                'total_price' => 1530305.00,
                'discount_price' => 0.00,
                'inver_rating' => '1.2 kVA',
                'total_output' => '3.8 kWh',
                'total_load' => '1200 W',
            ],
        ];

        // Create Inverter + Battery bundles
        foreach ($inverterBatteryBundles as $bundle) {
            Bundles::create($bundle);
        }

        // Create Solar+Inverter+Battery bundles
        foreach ($solarInverterBatteryBundles as $bundle) {
            Bundles::create($bundle);
        }

        $this->command->info('Bundles seeded successfully!');
        $this->command->info('Created ' . count($inverterBatteryBundles) . ' Inverter + Battery bundles');
        $this->command->info('Created ' . count($solarInverterBatteryBundles) . ' Solar+Inverter+Battery bundles');
    }
}
