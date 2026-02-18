<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bundles;
use App\Models\Material;
use App\Models\Product;
use App\Models\BundleMaterial;
use App\Models\BundleItems;
use App\Models\CustomService;

class BundleMaterialSeeder extends Seeder
{
    public function run(): void
    {
        $findMaterial = function ($name) {
            $material = Material::where('name', $name)->first();
            if ($material) return $material;
            $material = Material::where('name', 'like', '%' . $name . '%')->first();
            if ($material) return $material;
            $material = Material::whereRaw('? LIKE CONCAT("%", name, "%")', [$name])->first();
            return $material;
        };

        $findProduct = function ($name) use ($findMaterial) {
            // 1. Exact match
            $product = Product::where('title', $name)->first();
            if ($product) return $product;

            // 2. LIKE match
            $product = Product::where('title', 'like', '%' . $name . '%')->first();
            if ($product) return $product;

            // 3. Reverse LIKE
            $product = Product::whereRaw('? LIKE CONCAT("%", title, "%")', [$name])->first();
            if ($product) return $product;

            // 4. Case-insensitive match
            $product = Product::whereRaw('LOWER(title) = ?', [strtolower($name)])->first();
            if ($product) return $product;

            // 5. Fallback: find matching material and auto-create product
            $material = $findMaterial($name);
            if ($material) {
                $category = \App\Models\Category::first();
                $brand    = \App\Models\Brand::first();
                $product  = Product::create([
                    'title'          => $material->name,
                    'category_id'    => $category?->id,
                    'brand_id'       => $brand?->id,
                    'price'          => (float) ($material->selling_rate ?? $material->rate ?? 0),
                    'discount_price' => (float) ($material->selling_rate ?? $material->rate ?? 0),
                    'stock'          => 'In Stock',
                    'featured_image' => 'https://troosolar.hmstech.org/storage/products/d5c7f116-57ed-46ef-a659-337c94c308a9.png',
                ]);
                return $product;
            }

            return null;
        };

        $bundleConfigs = $this->getBundleConfigs();

        foreach ($bundleConfigs as $config) {
            $bundle = Bundles::where('title', $config['title'])->first();
            if (!$bundle) {
                $this->command->warn("Bundle not found: {$config['title']}");
                continue;
            }

            $this->command->info("Seeding data for: {$config['title']}");

            // Clear existing data for this bundle
            BundleItems::where('bundle_id', $bundle->id)->delete();
            BundleMaterial::where('bundle_id', $bundle->id)->delete();
            CustomService::where('bundle_id', $bundle->id)->delete();

            // Seed bundle_items (products for the Order List)
            foreach ($config['products'] as $prod) {
                $product = $findProduct($prod['name']);
                if ($product) {
                    BundleItems::create([
                        'bundle_id'  => $bundle->id,
                        'product_id' => $product->id,
                        'quantity'   => $prod['quantity'],
                    ]);
                } else {
                    $this->command->warn("  Product not found: {$prod['name']}");
                }
            }

            // Seed bundle_materials (physical installation materials)
            foreach ($config['materials'] as $mat) {
                $material = $findMaterial($mat['name']);
                if ($material) {
                    BundleMaterial::create([
                        'bundle_id'   => $bundle->id,
                        'material_id' => $material->id,
                        'quantity'    => $mat['quantity'],
                    ]);
                } else {
                    $this->command->warn("  Material not found: {$mat['name']}");
                }
            }

            // Seed custom_services (fees for the Invoice)
            foreach ($config['services'] as $svc) {
                CustomService::create([
                    'bundle_id'      => $bundle->id,
                    'title'          => $svc['title'],
                    'service_amount' => $svc['amount'] ?? 0,
                ]);
            }
        }

        $this->command->info('Bundle items, materials, and services seeded successfully!');
    }

    private function getBundleConfigs(): array
    {
        return [
            // ── 1. LitePower1213  (Inverter+Battery+Grid: 12V, 1.2kVA, 1.3kWh) ──
            [
                'title' => 'LitePower1213',
                'products' => [
                    ['name' => 'OG-1P1K2-T 12V 1.2kVA Yinergy Solar Hybrid Inverter', 'quantity' => 1],
                    ['name' => 'GCL 12100 12V 1.3kWh Cworth Energy Lithium Ion Battery', 'quantity' => 1],
                ],
                'materials' => [
                    ['name' => '6-Way Combiner Box', 'quantity' => 1],
                    ['name' => 'Battery Rack (1 Battery)', 'quantity' => 2],
                    ['name' => '1C x 10mm Battery Flexible Cable', 'quantity' => 6],
                    ['name' => '150A DC Breaker', 'quantity' => 1],
                    ['name' => '32A AC Breaker', 'quantity' => 2],
                    ['name' => 'Voltage Regulator', 'quantity' => 1],
                    ['name' => 'Smart Metering', 'quantity' => 1],
                    ['name' => '1C x 2.5mm AC Cable', 'quantity' => 50],
                    ['name' => 'AC Surge Protector', 'quantity' => 1],
                    ['name' => '32A By-Pass Switch', 'quantity' => 1],
                    ['name' => 'Trunking, flex pipe, Cable lugs for 1.2kVA+1.3kWh System', 'quantity' => 1],
                ],
                'services' => [
                    ['title' => 'Installation Fees for 1.2kVA+1.3kWh System', 'amount' => 0],
                    ['title' => 'Delivery Fees for 1.2kVA+1.3kWh System', 'amount' => 0],
                    ['title' => 'Inspection Fees', 'amount' => 0],
                ],
            ],

            // ── 2. SolarLitePower1213  (Solar+Inverter+Battery+Grid: 12V, 0.6kWp, 1.2kVA, 1.3kWh) ──
            [
                'title' => 'SolarLitePower1213',
                'products' => [
                    ['name' => '590W Bi-Facial Jinko Solar Panel', 'quantity' => 1],
                    ['name' => 'OG-1P1K2-T 12V 1.2kVA Yinergy Solar Hybrid Inverter', 'quantity' => 1],
                    ['name' => 'GCL 12100 12V 1.3kWh Cworth Energy Lithium Ion Battery', 'quantity' => 1],
                ],
                'materials' => [
                    ['name' => 'Solar Panel Mounting Rails for Aluminum roofs (1 Panel)', 'quantity' => 1],
                    ['name' => '6-Way Combiner Box', 'quantity' => 2],
                    ['name' => '150A PV Breaker', 'quantity' => 1],
                    ['name' => '1C x 4mm Solar Panel Flexible Cable', 'quantity' => 15],
                    ['name' => 'DC Surge Protector', 'quantity' => 1],
                    ['name' => 'Battery Rack (1 Battery)', 'quantity' => 1],
                    ['name' => '1C x 10mm Battery Flexible Cable', 'quantity' => 6],
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
                ],
                'services' => [
                    ['title' => 'Installation Fees for 0.6kWp+1.2kVA+1.3kWh System', 'amount' => 0],
                    ['title' => 'Delivery Fees for 0.6kWp+1.2kVA+1.3kWh System', 'amount' => 0],
                    ['title' => 'Inspection Fees', 'amount' => 0],
                ],
            ],

            // ── 3. LitePower1225  (Inverter+Battery+Grid: 12V, 1.2kVA, 2.5kWh) ──
            [
                'title' => 'LitePower1225',
                'products' => [
                    ['name' => 'OG-1P1K2-T 12V 1.2kVA Yinergy Solar Hybrid Inverter', 'quantity' => 1],
                    ['name' => 'GCL 12200 12V 2.5kWh Cworth Energy Lithium Ion Battery', 'quantity' => 1],
                ],
                'materials' => [
                    ['name' => '6-Way Combiner Box', 'quantity' => 1],
                    ['name' => 'Battery Rack (1 Battery)', 'quantity' => 2],
                    ['name' => '1C x 10mm Battery Flexible Cable', 'quantity' => 6],
                    ['name' => '150A DC Breaker', 'quantity' => 1],
                    ['name' => '32A AC Breaker', 'quantity' => 2],
                    ['name' => 'Voltage Regulator', 'quantity' => 1],
                    ['name' => 'Smart Metering', 'quantity' => 1],
                    ['name' => '1C x 2.5mm AC Cable', 'quantity' => 50],
                    ['name' => 'AC Surge Protector', 'quantity' => 1],
                    ['name' => '32A By-Pass Switch', 'quantity' => 1],
                    ['name' => 'Trunking, flex pipe, Cable lugs for 1.2kVA+2.5kWh System', 'quantity' => 1],
                ],
                'services' => [
                    ['title' => 'Installation Fees for 1.2kVA+2.5kWh System', 'amount' => 0],
                    ['title' => 'Delivery Fees for 1.2kVA+2.5kWh System', 'amount' => 0],
                    ['title' => 'Inspection Fees', 'amount' => 0],
                ],
            ],

            // ── 4. SolarLitePower1225  (Solar+Inverter+Battery+Grid: 12V, 1.2kWp, 1.2kVA, 2.5kWh) ──
            [
                'title' => 'SolarLitePower1225',
                'products' => [
                    ['name' => '455W Monofacial Jinko Solar Panel', 'quantity' => 2],
                    ['name' => 'OG-1P1K2-T 12V 1.2kVA Yinergy Solar Hybrid Inverter', 'quantity' => 1],
                    ['name' => 'GCL 12200 12V 2.5kWh Cworth Energy Lithium Ion Battery', 'quantity' => 1],
                ],
                'materials' => [
                    ['name' => 'Solar Panel Mounting Rails for Aluminum roofs (1 Panel)', 'quantity' => 2],
                    ['name' => '6-Way Combiner Box', 'quantity' => 2],
                    ['name' => '150A PV Breaker', 'quantity' => 1],
                    ['name' => '1C x 6mm Solar Panel Flexible Cable', 'quantity' => 30],
                    ['name' => 'DC Surge Protector', 'quantity' => 1],
                    ['name' => 'Battery Rack (1 Battery)', 'quantity' => 1],
                    ['name' => '1C x 16mm Battery Flexible Cable', 'quantity' => 6],
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
                ],
                'services' => [
                    ['title' => 'Installation Fees for 1.2kWp+1.2kVA+2.5kWh System', 'amount' => 0],
                    ['title' => 'Delivery Fees for 1.2kWp+1.2kVA+2.5kWh System', 'amount' => 0],
                    ['title' => 'Inspection Fees', 'amount' => 0],
                ],
            ],

            // ── 5. LitePower1238  (Inverter+Battery+Grid: 12V, 1.2kVA, 3.8kWh) ──
            [
                'title' => 'LitePower1238',
                'products' => [
                    ['name' => 'OG-1P1K2-T 12V 1.2kVA Yinergy Solar Hybrid Inverter', 'quantity' => 1],
                    ['name' => 'GCL 12300 12V 3.8kWh Cworth Energy Lithium Ion Battery', 'quantity' => 1],
                ],
                'materials' => [
                    ['name' => '6-Way Combiner Box', 'quantity' => 1],
                    ['name' => '1C x 16mm Battery Flexible Cable', 'quantity' => 6],
                    ['name' => '150A DC Breaker', 'quantity' => 1],
                    ['name' => '32A AC Breaker', 'quantity' => 2],
                    ['name' => 'Voltage Regulator', 'quantity' => 1],
                    ['name' => 'Smart Metering', 'quantity' => 1],
                    ['name' => '1C x 4mm AC Cable', 'quantity' => 50],
                    ['name' => 'AC Surge Protector', 'quantity' => 1],
                    ['name' => 'Earthing Rod', 'quantity' => 1],
                    ['name' => '32A By-Pass Switch', 'quantity' => 1],
                    ['name' => 'Trunking, flex pipe, Cable lugs for 1.2kVA+3.8kWh System', 'quantity' => 1],
                ],
                'services' => [
                    ['title' => 'Installation Fees for 1.2kVA+3.8kWh System', 'amount' => 0],
                    ['title' => 'Delivery Fees for 1.2kVA+3.8kWh System', 'amount' => 0],
                    ['title' => 'Inspection Fees', 'amount' => 0],
                ],
            ],

            // ── 6. SolarLitePower1238  (Solar+Inverter+Battery+Grid: 12V, 1.2kWp, 1.2kVA, 3.8kWh) ──
            [
                'title' => 'SolarLitePower1238',
                'products' => [
                    ['name' => '590W Bi-Facial Jinko Solar Panel', 'quantity' => 2],
                    ['name' => 'OG-1P1K2-T 12V 1.2kVA Yinergy Solar Hybrid Inverter', 'quantity' => 1],
                    ['name' => 'GCL 12300 12V 3.8kWh Cworth Energy Lithium Ion Battery', 'quantity' => 1],
                ],
                'materials' => [
                    ['name' => 'Solar Panel Mounting Rails for Aluminum roofs (1 Panel)', 'quantity' => 2],
                    ['name' => '6-Way Combiner Box', 'quantity' => 2],
                    ['name' => '150A PV Breaker', 'quantity' => 1],
                    ['name' => '1C x 6mm Solar Panel Flexible Cable', 'quantity' => 30],
                    ['name' => 'DC Surge Protector', 'quantity' => 1],
                    ['name' => 'Battery Rack (1 Battery)', 'quantity' => 1],
                    ['name' => '1C x 16mm Battery Flexible Cable', 'quantity' => 6],
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
                ],
                'services' => [
                    ['title' => 'Installation Fees for 1.2kWp+1.2kVA+3.8kWh System', 'amount' => 0],
                    ['title' => 'Delivery Fees for 1.2kWp+1.2kVA+3.8kWh System', 'amount' => 0],
                    ['title' => 'Inspection Fees', 'amount' => 0],
                ],
            ],
        ];
    }
}
