<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed material categories first, then materials, then products, then brands, then bundles
        $this->call([
            MaterialCategorySeeder::class,
            MaterialSeeder::class,
            ProductSeeder::class,
            BrandSeeder::class,
            BundleSeeder::class,
            BundleMaterialSeeder::class,
        ]);
    }
}
