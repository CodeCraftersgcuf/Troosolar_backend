<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bundles;
use App\Models\Material;
use App\Models\BundleMaterial;

class BundleMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if bundle materials already exist
        if (BundleMaterial::count() > 0) {
            $this->command->info('Bundle materials already exist. Skipping...');
            return;
        }

        // Helper function to find material by name (fuzzy match)
        $findMaterial = function ($name) {
            // Try exact match first
            $material = Material::where('name', $name)->first();
            if ($material) return $material;

            // Try partial match
            $material = Material::where('name', 'like', '%' . $name . '%')->first();
            if ($material) return $material;

            // Try reverse partial match (name contains search term)
            $material = Material::whereRaw('? LIKE CONCAT("%", name, "%")', [$name])->first();
            return $material;
        };

        // 1. Y1.2kVA+1.3kWh (Inverter + Battery) - First Image
        $bundle1 = Bundles::where('title', 'Y1.2kVA+1.3kWh')->first();
        if ($bundle1) {
            $materials1 = [
                ['name' => '6-Way Combiner Box', 'quantity' => 1],
                ['name' => 'GCL 12100 12V 1.3kWh Cworth Energy Lithium Ion Battery', 'quantity' => 1],
                ['name' => 'Battery Rack (1 Battery)', 'quantity' => 2],
                ['name' => '1C x 10mm Battery Flexible Cable', 'quantity' => 6],
                ['name' => 'OG-1P1K2-T 12V 1.2kVA Yinergy Solar Hybrid Inverter', 'quantity' => 1],
                ['name' => '150A DC Breaker', 'quantity' => 1],
                ['name' => '32A AC Breaker', 'quantity' => 2],
                ['name' => 'Voltage Regulator', 'quantity' => 1],
                ['name' => 'Smart Metering', 'quantity' => 1],
                ['name' => '1C x 2.5mm AC Cable', 'quantity' => 50],
                ['name' => 'AC Surge Protector', 'quantity' => 1],
                ['name' => '32A By-Pass Switch', 'quantity' => 1],
                ['name' => 'Trunking, flex pipe, Cable lugs for 1.2kVA+1.3kWh System', 'quantity' => 1],
                ['name' => 'Installation Fees for 1.2kVA+1.3kWh System', 'quantity' => 1],
                ['name' => 'Delivery Fees for 1.2kVA+1.3kWh System', 'quantity' => 1],
                ['name' => 'Inspection Fees', 'quantity' => 1],
            ];

            foreach ($materials1 as $mat) {
                $material = $findMaterial($mat['name']);
                if ($material) {
                    BundleMaterial::create([
                        'bundle_id' => $bundle1->id,
                        'material_id' => $material->id,
                        'quantity' => $mat['quantity'],
                    ]);
                } else {
                    $this->command->warn("Material not found: {$mat['name']}");
                }
            }
        }

        // 2. Y1.2kWp+1.2kVA+2.5kWh (Solar+Inverter+Battery) - Second Image
        $bundle2 = Bundles::where('title', 'Y1.2kWp+1.2kVA+2.5kW')->first();
        if ($bundle2) {
            $materials2 = [
                ['name' => '455W Monofacial Jinko Solar Panel', 'quantity' => 2],
                ['name' => 'Solar Panel Mounting Rails for Aluminum roofs (1 Panel)', 'quantity' => 2],
                ['name' => '6-Way Combiner Box', 'quantity' => 2],
                ['name' => '150A PV Breaker', 'quantity' => 1],
                ['name' => '1C x 6mm Solar Panel Flexible Cable', 'quantity' => 30],
                ['name' => 'DC Surge Protector', 'quantity' => 1],
                ['name' => 'GCL 12200 12V 2.5kWh Cworth Energy Lithium Ion Battery', 'quantity' => 1],
                ['name' => 'Battery Rack (1 Battery)', 'quantity' => 1],
                ['name' => '1C x 16mm Battery Flexible Cable', 'quantity' => 6],
                ['name' => 'OG-1P1K2-T 12V 1.2kVA Yinergy Solar Hybrid Inverter', 'quantity' => 1],
                ['name' => '150A DC Breaker', 'quantity' => 1],
                ['name' => '32A AC Breaker', 'quantity' => 2],
                ['name' => 'Voltage Regulator', 'quantity' => 1],
                ['name' => 'Smart Metering', 'quantity' => 1],
                ['name' => '1C x 4mm AC Cable', 'quantity' => 50],
                ['name' => 'AC Surge Protector', 'quantity' => 1],
                ['name' => '1C x 4mm Solar Panel Flexible Cable', 'quantity' => 25],
                ['name' => 'Earthing Rod', 'quantity' => 1],
                ['name' => '32A By-Pass Switch', 'quantity' => 1],
                ['name' => 'Trunking, flex pipe, Cable lugs for 1.2kWp+1.2kVA+2.5kWh System', 'quantity' => 1],
                ['name' => 'Installation Fees for 1.2kWp+1.2kVA+2.5kWh System', 'quantity' => 1],
                ['name' => 'Delivery Fees for 1.2kWp+1.2kVA+2.5kWh System', 'quantity' => 1],
                ['name' => 'Inspection Fees', 'quantity' => 1],
            ];

            foreach ($materials2 as $mat) {
                $material = $findMaterial($mat['name']);
                if ($material) {
                    BundleMaterial::create([
                        'bundle_id' => $bundle2->id,
                        'material_id' => $material->id,
                        'quantity' => $mat['quantity'],
                    ]);
                } else {
                    $this->command->warn("Material not found: {$mat['name']}");
                }
            }
        }

        // 3. Y0.6kWp+1.2kVA+1.3kWh (Solar+Inverter+Battery) - Third Image
        $bundle3 = Bundles::where('title', 'Y0.6kWp+1.2kVA+1.3kW')->first();
        if ($bundle3) {
            $materials3 = [
                ['name' => '590W Bi-Facial Jinko Solar Panel', 'quantity' => 1],
                ['name' => 'Solar Panel Mounting Rails for Aluminum roofs (1 Panel)', 'quantity' => 1],
                ['name' => '6-Way Combiner Box', 'quantity' => 2],
                ['name' => '150A PV Breaker', 'quantity' => 1],
                ['name' => '1C x 4mm Solar Panel Flexible Cable', 'quantity' => 15],
                ['name' => 'DC Surge Protector', 'quantity' => 1],
                ['name' => 'GCL 12100 12V 1.3kWh Cworth Energy Lithium Ion Battery', 'quantity' => 1],
                ['name' => 'Battery Rack (1 Battery)', 'quantity' => 1],
                ['name' => '1C x 10mm Battery Flexible Cable', 'quantity' => 6],
                ['name' => 'OG-1P1K2-T 12V 1.2kVA Yinergy Solar Hybrid Inverter', 'quantity' => 1],
                ['name' => '150A DC Breaker', 'quantity' => 1],
                ['name' => '32A AC Breaker', 'quantity' => 2],
                ['name' => 'Voltage Regulator', 'quantity' => 1],
                ['name' => 'Smart Metering', 'quantity' => 1],
                ['name' => '1C x 2.5mm AC Cable', 'quantity' => 50],
                ['name' => 'AC Surge Protector', 'quantity' => 1],
                ['name' => '1C x 2.5mm Earth Cable', 'quantity' => 25],
                ['name' => 'Earthing Rod', 'quantity' => 1],
                ['name' => '32A By-Pass Switch', 'quantity' => 1],
                ['name' => 'Trunking, flex pipe, Cable lugs for 0.6kWp+1.2kVA+1.3kWh System', 'quantity' => 1],
                ['name' => 'Installation Fees for 0.6kWp+1.2kVA+1.3kWh System', 'quantity' => 1],
                ['name' => 'Delivery Fees for 0.6kWp+1.2kVA+1.3kWh System', 'quantity' => 1],
                ['name' => 'Inspection Fees', 'quantity' => 1],
            ];

            foreach ($materials3 as $mat) {
                $material = $findMaterial($mat['name']);
                if ($material) {
                    BundleMaterial::create([
                        'bundle_id' => $bundle3->id,
                        'material_id' => $material->id,
                        'quantity' => $mat['quantity'],
                    ]);
                } else {
                    $this->command->warn("Material not found: {$mat['name']}");
                }
            }
        }

        // 4. Y1.2kWp+1.2kVA+3.8kWh (Solar+Inverter+Battery) - Fourth Image
        $bundle4 = Bundles::where('title', 'Y1.2kWp+1.2kVA+3.8kW')->first();
        if ($bundle4) {
            $materials4 = [
                ['name' => '590W Bi-Facial Jinko Solar Panel', 'quantity' => 2],
                ['name' => 'Solar Panel Mounting Rails for Aluminum roofs (1 Panel)', 'quantity' => 2],
                ['name' => '6-Way Combiner Box', 'quantity' => 2],
                ['name' => '150A PV Breaker', 'quantity' => 1],
                ['name' => '1C x 6mm Solar Panel Flexible Cable', 'quantity' => 30],
                ['name' => 'DC Surge Protector', 'quantity' => 1],
                ['name' => 'GCL 12300 12V 3.8kWh Cworth Energy Lithium Ion Battery', 'quantity' => 1],
                ['name' => 'Battery Rack (1 Battery)', 'quantity' => 1],
                ['name' => '1C x 16mm Battery Flexible Cable', 'quantity' => 6],
                ['name' => 'OG-1P1K2-T 12V 1.2kVA Yinergy Solar Hybrid Inverter', 'quantity' => 1],
                ['name' => '150A DC Breaker', 'quantity' => 1],
                ['name' => '32A AC Breaker', 'quantity' => 2],
                ['name' => 'Voltage Regulator', 'quantity' => 1],
                ['name' => 'Smart Metering', 'quantity' => 1],
                ['name' => '1C x 4mm AC Cable', 'quantity' => 50],
                ['name' => 'AC Surge Protector', 'quantity' => 1],
                ['name' => '1C x 2.5mm Earth Cable', 'quantity' => 25],
                ['name' => 'Earthing Rod', 'quantity' => 1],
                ['name' => '32A By-Pass Switch', 'quantity' => 1],
                ['name' => 'Trunking, flex pipe, Cable lugs for 1.2kWp+1.2kVA+3.8kWh System', 'quantity' => 1],
                ['name' => 'Installation Fees for 1.2kWp+1.2kVA+3.8kWh System', 'quantity' => 1],
                ['name' => 'Delivery Fees for 1.2kWp+1.2kVA+3.8kWh System', 'quantity' => 1],
                ['name' => 'Inspection Fees', 'quantity' => 1],
            ];

            foreach ($materials4 as $mat) {
                $material = $findMaterial($mat['name']);
                if ($material) {
                    BundleMaterial::create([
                        'bundle_id' => $bundle4->id,
                        'material_id' => $material->id,
                        'quantity' => $mat['quantity'],
                    ]);
                } else {
                    $this->command->warn("Material not found: {$mat['name']}");
                }
            }
        }

        // 5. Y1.2kVA+3.8kWh (Inverter + Battery) - Fifth Image
        $bundle5 = Bundles::where('title', 'Y1.2kVA+3.8kWh')->first();
        if ($bundle5) {
            $materials5 = [
                ['name' => '6-Way Combiner Box', 'quantity' => 1],
                ['name' => 'GCL 12300 12V 3.8kWh Cworth Energy Lithium Ion Battery', 'quantity' => 1],
                ['name' => '1C x 16mm Battery Flexible Cable', 'quantity' => 6],
                ['name' => 'OG-1P1K2-T 12V 1.2kVA Yinergy Solar Hybrid Inverter', 'quantity' => 1],
                ['name' => '150A DC Breaker', 'quantity' => 1],
                ['name' => '32A AC Breaker', 'quantity' => 2],
                ['name' => 'Voltage Regulator', 'quantity' => 1],
                ['name' => 'Smart Metering', 'quantity' => 1],
                ['name' => '1C x 4mm AC Cable', 'quantity' => 50],
                ['name' => 'AC Surge Protector', 'quantity' => 1],
                ['name' => 'Earthing Rod', 'quantity' => 1],
                ['name' => '32A By-Pass Switch', 'quantity' => 1],
                ['name' => 'Trunking, flex pipe, Cable lugs for 1.2kVA+3.8kWh System', 'quantity' => 1],
                ['name' => 'Installation Fees for 1.2kVA+3.8kWh System', 'quantity' => 1],
                ['name' => 'Delivery Fees for 1.2kVA+3.8kWh System', 'quantity' => 1],
                ['name' => 'Inspection Fees', 'quantity' => 1],
            ];

            foreach ($materials5 as $mat) {
                $material = $findMaterial($mat['name']);
                if ($material) {
                    BundleMaterial::create([
                        'bundle_id' => $bundle5->id,
                        'material_id' => $material->id,
                        'quantity' => $mat['quantity'],
                    ]);
                } else {
                    $this->command->warn("Material not found: {$mat['name']}");
                }
            }
        }

        $this->command->info('Bundle materials seeded successfully!');
    }
}
