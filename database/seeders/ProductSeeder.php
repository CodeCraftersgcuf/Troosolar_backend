<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Material;
use App\Models\MaterialCategory;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if products from materials already exist (check by price = 1000)
        $existingProducts = Product::where('price', 1000)->whereNotNull('featured_image')->count();
        if ($existingProducts > 0) {
            $this->command->info('Products from materials already exist. Skipping...');
            return;
        }

        // Fallback image URL
        $fallbackImage = 'https://troosolar.hmstech.org/storage/products/e212b55b-057a-4a39-8d80-d241169cdac0.png';

        // Get or create product categories (map from material categories)
        $categoryMap = $this->getOrCreateCategories();

        // Get or create a default brand
        $defaultBrand = $this->getOrCreateDefaultBrand($categoryMap);

        // Get all materials (excluding extra services - T, U, V)
        $materials = Material::with('category')
            ->whereHas('category', function ($query) {
                // Exclude extra services (Installation Fees, Delivery Fees, Inspection Fees)
                $query->whereNotIn('code', ['T', 'U', 'V']);
            })
            ->get();

        $created = 0;
        $skipped = 0;

        foreach ($materials as $material) {
            // Skip if product with same title already exists
            $existingProduct = Product::where('title', $material->name)->first();
            if ($existingProduct) {
                $skipped++;
                continue;
            }

            // Map material category to product category
            $productCategory = $this->mapMaterialCategoryToProductCategory($material->category->name, $categoryMap);

            // Create product
            Product::create([
                'title' => $material->name,
                'category_id' => $productCategory->id,
                'brand_id' => $defaultBrand->id,
                'price' => 1000.00,
                'discount_price' => 1000.00,
                'stock' => 'In Stock',
                'installation_price' => 0.00,
                'top_deal' => false,
                'installation_compulsory' => false,
                'featured_image' => $fallbackImage,
            ]);

            $created++;
        }

        $this->command->info("Products seeded successfully! Created: {$created}, Skipped: {$skipped}");
    }

    /**
     * Get or create product categories based on material categories
     */
    private function getOrCreateCategories()
    {
        $categoryMap = [];
        $materialCategoryMap = [
            'SOLAR PANELS' => 'Solar Panels',
            'SOLAR HYBRID INVERTERS' => 'Inverters',
            'ALL IN ONE SYSTEMS' => 'All In One Systems',
            'WIFI STICKS' => 'Accessories',
            'LITHIUM BATTERIES' => 'Batteries',
            'MOUNTING STRUCTURE' => 'Mounting & Installation',
            'COMBINER BOX' => 'Electrical Components',
            'PV BREAKERS' => 'Electrical Components',
            'SOLAR PV DC FLEXIBLE CABLE' => 'Cables & Wiring',
            'SURGE PROTECTORS' => 'Electrical Components',
            'BATTERY RACK' => 'Mounting & Installation',
            'DC FLEXIBLE CABLE' => 'Cables & Wiring',
            'DC BREAKER' => 'Electrical Components',
            'AC BREAKER' => 'Electrical Components',
            'AC CABLE' => 'Cables & Wiring',
            'BUSBAR' => 'Electrical Components',
            'EARTH ROD & EARTH CABLE' => 'Electrical Components',
            'BYPASS SWITCH' => 'Electrical Components',
            'ACCESSORIES' => 'Accessories',
        ];

        foreach ($materialCategoryMap as $materialCatName => $productCatTitle) {
            // Check if category exists
            $category = Category::where('title', $productCatTitle)->first();
            
            if (!$category) {
                $category = Category::create([
                    'title' => $productCatTitle,
                    'icon' => null,
                ]);
            }

            $categoryMap[$materialCatName] = $category;
        }

        return $categoryMap;
    }

    /**
     * Get or create default brand
     */
    private function getOrCreateDefaultBrand($categoryMap)
    {
        $defaultBrand = Brand::first();
        if (!$defaultBrand) {
            $firstCategory = reset($categoryMap);
            $defaultBrand = Brand::create([
                'title' => 'Default Brand',
                'category_id' => $firstCategory ? $firstCategory->id : null,
            ]);
        }
        return $defaultBrand;
    }

    /**
     * Map material category to product category
     */
    private function mapMaterialCategoryToProductCategory($materialCategoryName, $categoryMap)
    {
        // Direct mapping
        if (isset($categoryMap[$materialCategoryName])) {
            return $categoryMap[$materialCategoryName];
        }

        // Fallback: use first available category or create a default
        $defaultCategory = Category::where('title', 'Accessories')->first();
        if (!$defaultCategory) {
            $defaultCategory = Category::first();
            if (!$defaultCategory) {
                $defaultCategory = Category::create([
                    'title' => 'Solar Products',
                    'icon' => null,
                ]);
            }
        }

        return $defaultCategory;
    }
}
