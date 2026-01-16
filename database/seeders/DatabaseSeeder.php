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
        // Seed material categories first, then materials, then bundles, then bundle materials
        $this->call([
            MaterialCategorySeeder::class,
            MaterialSeeder::class,
            BundleSeeder::class,
            BundleMaterialSeeder::class,
        ]);
    }
}
