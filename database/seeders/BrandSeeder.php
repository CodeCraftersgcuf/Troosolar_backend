<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

class BrandSeeder extends Seeder
{
    /**
     * Seed brands (Jinko, Longi, Itel, Powerchina, Yinergy, Cworth, etc.)
     * and assign products to them so they appear in the category brand dropdown.
     */
    public function run(): void
    {
        $brandTitles = [
            'Jinko',
            'Longi',
            'Itel',
            'Powerchina',
            'Yinergy',
            'Cworth',
        ];

        $brands = [];
        foreach ($brandTitles as $title) {
            $brand = Brand::firstOrCreate(
                ['title' => $title],
                ['icon' => null, 'category_id' => null]
            );
            $brands[] = $brand;
        }

        if ($brands === []) {
            $this->command->info('No brands to assign products to.');
            return;
        }

        // Assign products to these brands so they show in category brand dropdown
        $categories = Category::has('products')->get();
        $brandIndex = 0;
        $assigned = 0;

        foreach ($categories as $category) {
            $products = Product::where('category_id', $category->id)->get();
            foreach ($products as $product) {
                $product->update(['brand_id' => $brands[$brandIndex]->id]);
                $brandIndex = ($brandIndex + 1) % count($brands);
                $assigned++;
            }
        }

        $this->command->info('BrandSeeder: ' . count($brands) . ' brands ensured; ' . $assigned . ' products assigned.');
    }
}
