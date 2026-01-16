<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\MaterialCategory;

class MaterialCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if categories already exist
        if (MaterialCategory::count() > 0) {
            $this->command->info('Material categories already exist. Skipping...');
            return;
        }

        $categories = [
            ['code' => 'A', 'name' => 'SOLAR PANELS', 'description' => 'Solar panel products', 'sort_order' => 1],
            ['code' => 'B', 'name' => 'SOLAR HYBRID INVERTERS', 'description' => 'Solar hybrid inverter products', 'sort_order' => 2],
            ['code' => 'C', 'name' => 'ALL IN ONE SYSTEMS', 'description' => 'All-in-one solar systems', 'sort_order' => 3],
            ['code' => 'D', 'name' => 'WIFI STICKS', 'description' => 'WiFi sticks for solar inverters', 'sort_order' => 4],
            ['code' => 'E', 'name' => 'LITHIUM BATTERIES', 'description' => 'Lithium-ion battery products', 'sort_order' => 5],
            ['code' => 'F', 'name' => 'MOUNTING STRUCTURE', 'description' => 'Solar panel mounting structures', 'sort_order' => 6],
            ['code' => 'G', 'name' => 'COMBINER BOX', 'description' => 'Combiner boxes for solar systems', 'sort_order' => 7],
            ['code' => 'H', 'name' => 'PV BREAKERS', 'description' => 'PV breakers for solar systems', 'sort_order' => 8],
            ['code' => 'I', 'name' => 'SOLAR PV DC FLEXIBLE CABLE', 'description' => 'DC flexible cables for solar panels', 'sort_order' => 9],
            ['code' => 'J', 'name' => 'SURGE PROTECTORS', 'description' => 'Surge protection devices', 'sort_order' => 10],
            ['code' => 'K', 'name' => 'BATTERY RACK', 'description' => 'Battery mounting racks', 'sort_order' => 11],
            ['code' => 'L', 'name' => 'DC FLEXIBLE CABLE', 'description' => 'DC flexible cables for batteries', 'sort_order' => 12],
            ['code' => 'M', 'name' => 'DC BREAKER', 'description' => 'DC breakers for solar systems', 'sort_order' => 13],
            ['code' => 'N', 'name' => 'AC BREAKER', 'description' => 'AC breakers for solar systems', 'sort_order' => 14],
            ['code' => 'O', 'name' => 'AC CABLE', 'description' => 'AC cables for solar systems', 'sort_order' => 15],
            ['code' => 'P', 'name' => 'BUSBAR', 'description' => 'Busbars for electrical connections', 'sort_order' => 16],
            ['code' => 'Q', 'name' => 'EARTH ROD & EARTH CABLE', 'description' => 'Earthing equipment', 'sort_order' => 17],
            ['code' => 'R', 'name' => 'BYPASS SWITCH', 'description' => 'Bypass switches for solar systems', 'sort_order' => 18],
            ['code' => 'S', 'name' => 'ACCESSORIES', 'description' => 'Various solar system accessories', 'sort_order' => 19],
            ['code' => 'T', 'name' => 'INSTALLATION FEES', 'description' => 'Installation service fees', 'sort_order' => 20],
            ['code' => 'U', 'name' => 'DELIVERY/LOGISTICS FEES', 'description' => 'Delivery and logistics fees', 'sort_order' => 21],
            ['code' => 'V', 'name' => 'INSPECTION FEES', 'description' => 'Inspection service fees', 'sort_order' => 22],
        ];

        foreach ($categories as $category) {
            MaterialCategory::create($category);
        }

        $this->command->info('Material categories seeded successfully!');
    }
}
