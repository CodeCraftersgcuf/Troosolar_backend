<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Helpers\ResponseHelper;
use Exception;

class SeederController extends Controller
{
    /**
     * Run all seeders globally
     * This will only run seeders that haven't been run before (based on data existence)
     */
    public function runAllSeeders()
    {
        try {
            $results = [];
            
            // Check and run MaterialCategorySeeder
            $categoryCount = DB::table('material_categories')->count();
            if ($categoryCount == 0) {
                Artisan::call('db:seed', ['--class' => 'MaterialCategorySeeder']);
                $results['material_categories'] = 'Seeded successfully';
            } else {
                $results['material_categories'] = 'Already seeded (skipped)';
            }

            // Check and run MaterialSeeder
            $materialCount = DB::table('materials')->count();
            if ($materialCount == 0) {
                Artisan::call('db:seed', ['--class' => 'MaterialSeeder']);
                $results['materials'] = 'Seeded successfully';
            } else {
                $results['materials'] = 'Already seeded (skipped)';
            }

            // Check and run BundleSeeder
            $bundleCount = DB::table('bundles')
                ->whereIn('bundle_type', ['Inverter + Battery', 'Solar+Inverter+Battery'])
                ->count();
            if ($bundleCount == 0) {
                Artisan::call('db:seed', ['--class' => 'BundleSeeder']);
                $results['bundles'] = 'Seeded successfully';
            } else {
                $results['bundles'] = 'Already seeded (skipped)';
            }

            // Check and run BundleMaterialSeeder
            $bundleMaterialCount = DB::table('bundle_materials')->count();
            if ($bundleMaterialCount == 0) {
                Artisan::call('db:seed', ['--class' => 'BundleMaterialSeeder']);
                $results['bundle_materials'] = 'Seeded successfully';
            } else {
                $results['bundle_materials'] = 'Already seeded (skipped)';
            }

            return ResponseHelper::success($results, 'Seeders executed successfully');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to run seeders: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Run a specific seeder
     */
    public function runSeeder(Request $request)
    {
        try {
            $seederClass = $request->input('seeder');
            
            if (!$seederClass) {
                return ResponseHelper::error('Seeder class name is required', 400);
            }

            // Validate seeder class exists
            $seederPath = database_path('seeders/' . $seederClass . '.php');
            if (!file_exists($seederPath)) {
                return ResponseHelper::error('Seeder class not found', 404);
            }

            Artisan::call('db:seed', ['--class' => $seederClass]);
            
            return ResponseHelper::success(null, "Seeder {$seederClass} executed successfully");
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to run seeder: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Run BundleSeeder specifically
     */
    public function runBundleSeeder()
    {
        try {
            $bundleCount = DB::table('bundles')
                ->whereIn('bundle_type', ['Inverter + Battery', 'Solar+Inverter+Battery'])
                ->count();
            
            if ($bundleCount > 0) {
                return ResponseHelper::success(
                    ['status' => 'Already seeded (skipped)', 'count' => $bundleCount],
                    'Bundles already exist. Skipping seeder.'
                );
            }

            Artisan::call('db:seed', ['--class' => 'BundleSeeder']);
            
            $newCount = DB::table('bundles')
                ->whereIn('bundle_type', ['Inverter + Battery', 'Solar+Inverter+Battery'])
                ->count();
            
            return ResponseHelper::success(
                ['status' => 'Seeded successfully', 'count' => $newCount],
                'Bundle seeder executed successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to run bundle seeder: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Run BundleMaterialSeeder specifically
     */
    public function runBundleMaterialSeeder()
    {
        try {
            $bundleMaterialCount = DB::table('bundle_materials')->count();
            
            if ($bundleMaterialCount > 0) {
                return ResponseHelper::success(
                    ['status' => 'Already seeded (skipped)', 'count' => $bundleMaterialCount],
                    'Bundle materials already exist. Skipping seeder.'
                );
            }

            Artisan::call('db:seed', ['--class' => 'BundleMaterialSeeder']);
            
            $newCount = DB::table('bundle_materials')->count();
            
            return ResponseHelper::success(
                ['status' => 'Seeded successfully', 'count' => $newCount],
                'Bundle material seeder executed successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to run bundle material seeder: ' . $e->getMessage(), 500);
        }
    }
}
