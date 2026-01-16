<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Material;
use App\Models\MaterialCategory;

class MaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if materials already exist
        if (Material::count() > 0) {
            $this->command->info('Materials already exist. Skipping...');
            return;
        }

        // Get categories by code
        $categories = MaterialCategory::all()->keyBy('code');

        $materials = [
            // A. SOLAR PANELS
            ['category' => 'A', 'name' => '455W Monofacial Jinko Solar Panel', 'unit' => 'Nos', 'warranty' => 10],
            ['category' => 'A', 'name' => '530W Bi-Facial Power China Solar Panel', 'unit' => 'Nos', 'warranty' => 15],
            ['category' => 'A', 'name' => '550W Monofacial Power China Solar Panel', 'unit' => 'Nos', 'warranty' => 15],
            ['category' => 'A', 'name' => '550W Bi-Facial Power China Solar Panel', 'unit' => 'Nos', 'warranty' => 15],
            ['category' => 'A', 'name' => '600W Monofacial Power China Solar Panel', 'unit' => 'Nos', 'warranty' => 15],
            ['category' => 'A', 'name' => '600W Bi-Facial Power China Solar Panel', 'unit' => 'Nos', 'warranty' => 15],

            // B. SOLAR HYBRID INVERTERS
            ['category' => 'B', 'name' => '1.5kW Itel Hybrid Inverter', 'unit' => 'Nos', 'warranty' => 2],
            ['category' => 'B', 'name' => '3kVA Felicity Solar Hybrid Inverter', 'unit' => 'Nos', 'warranty' => 3],
            ['category' => 'B', 'name' => '5kVA Felicity Solar Hybrid Inverter', 'unit' => 'Nos', 'warranty' => 3],
            ['category' => 'B', 'name' => '5kVA Power China Solar Hybrid Inverter', 'unit' => 'Nos', 'warranty' => 9],
            ['category' => 'B', 'name' => '8kVA Power China Solar Hybrid Inverter', 'unit' => 'Nos', 'warranty' => 9],
            ['category' => 'B', 'name' => '10kVA Power China Solar Hybrid Inverter', 'unit' => 'Nos', 'warranty' => 9],
            ['category' => 'B', 'name' => '15kVA Power China Solar Hybrid Inverter', 'unit' => 'Nos', 'warranty' => 9],
            ['category' => 'B', 'name' => '20kVA Power China Solar Hybrid Inverter', 'unit' => 'Nos', 'warranty' => 9],

            // C. ALL IN ONE SYSTEMS
            ['category' => 'C', 'name' => '1.5kVA+1.2kWh Smaidi All In One System', 'unit' => 'Nos', 'warranty' => 2],
            ['category' => 'C', 'name' => '3kVA+3kWh Felicity Solar All In One System', 'unit' => 'Nos', 'warranty' => 2],
            ['category' => 'C', 'name' => '5kVA+5kWh LFP Felicity Solar All In One System', 'unit' => 'Nos', 'warranty' => 5],
            ['category' => 'C', 'name' => '5kVA+10kWh LFP Felicity Solar All In One System', 'unit' => 'Nos', 'warranty' => 5],
            ['category' => 'C', 'name' => '10kVA+10kWh LFP Felicity Solar All In One System', 'unit' => 'Nos', 'warranty' => 5],
            ['category' => 'C', 'name' => '10kVA+20kWh LFP Felicity Solar All In One System', 'unit' => 'Nos', 'warranty' => 5],

            // D. WIFI STICKS
            ['category' => 'D', 'name' => 'Power China Solar Hybrid Inverter Wifi Stick', 'unit' => 'Nos', 'warranty' => 2],
            ['category' => 'D', 'name' => 'Felicity Solar Hybrid Inverter Wifi Stick', 'unit' => 'Nos', 'warranty' => 3],

            // E. LITHIUM BATTERIES
            ['category' => 'E', 'name' => '5.12V 5.12kWh Power China Lithium Ion Battery', 'unit' => 'Nos', 'warranty' => 5],
            ['category' => 'E', 'name' => '48V 10kWh Cworth Energy Lithium Ion Battery Wall Mount', 'unit' => 'Nos', 'warranty' => 7],
            ['category' => 'E', 'name' => '48V 10kWh Power China Lithium Ion Battery Wall Mount', 'unit' => 'Nos', 'warranty' => 5],
            ['category' => 'E', 'name' => '48V 10kWh Felicity Solar Lithium Ion Battery Wall Mount', 'unit' => 'Nos', 'warranty' => 5],
            ['category' => 'E', 'name' => '48V 15kWh Power China Lithium Ion Battery Wall Mount', 'unit' => 'Nos', 'warranty' => 5],
            ['category' => 'E', 'name' => '48V 20kWh Power China Lithium Ion Battery Wall Mount', 'unit' => 'Nos', 'warranty' => 5],
            ['category' => 'E', 'name' => '48V 30kWh Power China Lithium Ion Battery Wall Mount', 'unit' => 'Nos', 'warranty' => 5],
            ['category' => 'E', 'name' => '48V 40kWh Power China Lithium Ion Battery Wall Mount', 'unit' => 'Nos', 'warranty' => 5],
            ['category' => 'E', 'name' => '48V 50kWh Power China Lithium Ion Battery Wall Mount', 'unit' => 'Nos', 'warranty' => 5],

            // F. MOUNTING STRUCTURE
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Aluminum roofs (1 Panel)', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Aluminum roofs (2 Panels)', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Aluminum roofs (4 Panels)', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Aluminum roofs (6 Panels)', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Aluminum roofs (8 Panels)', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Aluminum roofs (10 Panels)', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Concrete/Tile roofs (1 Panel)', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Concrete/Tile roofs (2 Panels)', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Concrete/Tile roofs (4 Panels)', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Concrete/Tile roofs (6 Panels)', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Concrete/Tile roofs (8 Panels)', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'F', 'name' => 'Solar Panel Mounting Rails for Concrete/Tile roofs (10 Panels)', 'unit' => 'Nos', 'warranty' => 1],

            // G. COMBINER BOX
            ['category' => 'G', 'name' => '6-Way Combiner Box', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'G', 'name' => '7-Way Combiner Box', 'unit' => 'Nos', 'warranty' => 1],

            // H. PV BREAKERS
            ['category' => 'H', 'name' => '150A PV Breaker', 'unit' => 'Nos', 'warranty' => 1],

            // I. SOLAR PV DC FLEXIBLE CABLE
            ['category' => 'I', 'name' => '1C x 2.5mm Solar Panel Flexible Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'I', 'name' => '1C x 4mm Solar Panel Flexible Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'I', 'name' => '1C x 6mm Solar Panel Flexible Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'I', 'name' => '1C x 10mm Solar Panel Flexible Cable', 'unit' => 'Mtrs', 'warranty' => 1],

            // J. SURGE PROTECTORS
            ['category' => 'J', 'name' => '1C Surge Protector', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'J', 'name' => '2C Surge Protector', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'J', 'name' => '4C (3 Phase) Surge Protector', 'unit' => 'Nos', 'warranty' => 1],

            // K. BATTERY RACK
            ['category' => 'K', 'name' => 'Battery Rack (1 Battery)', 'unit' => 'Nos', 'warranty' => 1],

            // L. DC FLEXIBLE CABLE
            ['category' => 'L', 'name' => '1C x 10mm Battery Flexible Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'L', 'name' => '1C x 16mm Battery Flexible Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'L', 'name' => '1C x 25mm Battery Flexible Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'L', 'name' => '1C x 35mm Battery Flexible Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'L', 'name' => '1C x 50mm Battery Flexible Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'L', 'name' => '1C x 70mm Battery Flexible Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'L', 'name' => '1C x 95mm Battery Flexible Cable', 'unit' => 'Mtrs', 'warranty' => 1],

            // M. DC BREAKER
            ['category' => 'M', 'name' => '400A DC Breaker', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'M', 'name' => '250A DC Breaker', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'M', 'name' => '150A DC Breaker', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'M', 'name' => '100A DC Breaker', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'M', 'name' => '63A DC Breaker', 'unit' => 'Nos', 'warranty' => 1],

            // N. AC BREAKER
            ['category' => 'N', 'name' => '32A AC Breaker', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'N', 'name' => '40A AC Breaker', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'N', 'name' => '63A AC Breaker', 'unit' => 'Nos', 'warranty' => 1],

            // O. AC CABLE
            ['category' => 'O', 'name' => '1C x 2.5mm AC Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'O', 'name' => '1C x 4mm AC Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'O', 'name' => '1C x 6mm AC Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'O', 'name' => '1C x 10mm AC Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'O', 'name' => '1C x 16mm AC Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'O', 'name' => '1C x 25mm AC Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'O', 'name' => '1C x 35mm AC Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'O', 'name' => '1C x 50mm AC Cable', 'unit' => 'Mtrs', 'warranty' => 1],

            // P. BUSBAR
            ['category' => 'P', 'name' => '1C AC Busbar', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'P', 'name' => '2C AC Busbar', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'P', 'name' => '3 Phase AC Busbar', 'unit' => 'Nos', 'warranty' => 1],

            // Q. EARTH ROD & EARTH CABLE
            ['category' => 'Q', 'name' => 'Earthing Rod', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'Q', 'name' => '1C x 2.5mm Earth Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'Q', 'name' => '1C x 4mm Earth Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'Q', 'name' => '1C x 6mm Earth Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'Q', 'name' => '1C x 10mm Earth Cable', 'unit' => 'Mtrs', 'warranty' => 1],
            ['category' => 'Q', 'name' => '1C x 16mm Earth Cable', 'unit' => 'Mtrs', 'warranty' => 1],

            // R. BYPASS SWITCH
            ['category' => 'R', 'name' => '32A By-Pass Switch', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'R', 'name' => '63A By-Pass Switch', 'unit' => 'Nos', 'warranty' => 1],

            // S. ACCESSORIES
            ['category' => 'S', 'name' => 'Voltage Regulator', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'S', 'name' => 'Smart Metering', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'S', 'name' => 'Trunking, flex pipe, Cable lugs for 0.5kVA-1kWh ESS System', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'S', 'name' => 'Trunking, flex pipe, Cable lugs for 1.5kVA+1.2kWh ESS System', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'S', 'name' => 'Trunking, flex pipe, Cable lugs for 3kVA+3kWh ESS System', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'S', 'name' => 'Trunking, flex pipe, Cable lugs for 5kVA+5kWh ESS System', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'S', 'name' => 'Trunking, flex pipe, Cable lugs for 5kVA+10kWh ESS System', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'S', 'name' => 'Trunking, flex pipe, Cable lugs for 10kVA+10kWh ESS System', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'S', 'name' => 'Trunking, flex pipe, Cable lugs for 10kVA+20kWh ESS System', 'unit' => 'Nos', 'warranty' => 1],
            ['category' => 'S', 'name' => 'Trunking, flex pipe, Cable lugs for 5kVA+16kWh System', 'unit' => 'Nos', 'warranty' => 1],

            // T. INSTALLATION FEES
            ['category' => 'T', 'name' => 'Installation Fees for 0.5kVA-1kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'T', 'name' => 'Installation Fees for 1.5kVA+1.2kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'T', 'name' => 'Installation Fees for 3kVA+3kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'T', 'name' => 'Installation Fees for 5kVA+5kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'T', 'name' => 'Installation Fees for 5kVA+10kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'T', 'name' => 'Installation Fees for 10kVA+10kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'T', 'name' => 'Installation Fees for 10kVA+20kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'T', 'name' => 'Installation Fees for 5kVA+16kWh System', 'unit' => 'Nos', 'warranty' => null],

            // U. DELIVERY/LOGISTICS FEES
            ['category' => 'U', 'name' => 'Delivery Fees for 0.5kVA-1kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'U', 'name' => 'Delivery Fees for 1.5kVA+1.2kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'U', 'name' => 'Delivery Fees for 3kVA+3kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'U', 'name' => 'Delivery Fees for 5kVA+5kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'U', 'name' => 'Delivery Fees for 5kVA+10kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'U', 'name' => 'Delivery Fees for 10kVA+10kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'U', 'name' => 'Delivery Fees for 10kVA+20kWh ESS System', 'unit' => 'Nos', 'warranty' => null],
            ['category' => 'U', 'name' => 'Delivery Fees for 5kVA+16kWh System', 'unit' => 'Nos', 'warranty' => null],

            // V. INSPECTION FEES
            ['category' => 'V', 'name' => 'Inspection Fees', 'unit' => 'Nos', 'warranty' => null],
        ];

        foreach ($materials as $material) {
            $category = $categories[$material['category']] ?? null;
            if ($category) {
                Material::create([
                    'material_category_id' => $category->id,
                    'name' => $material['name'],
                    'unit' => $material['unit'],
                    'warranty' => $material['warranty'],
                    'rate' => 0.00,
                    'selling_rate' => 0.00,
                    'profit' => 0.00,
                ]);
            }
        }

        $this->command->info('Materials seeded successfully!');
    }
}
