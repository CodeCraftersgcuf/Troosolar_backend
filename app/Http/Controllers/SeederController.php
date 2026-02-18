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

            // Check and run ProductSeeder
            $productCount = DB::table('products')->where('price', 1000)->whereNotNull('featured_image')->count();
            if ($productCount == 0) {
                Artisan::call('db:seed', ['--class' => 'ProductSeeder']);
                $results['products'] = 'Seeded successfully';
            } else {
                $results['products'] = 'Already seeded (skipped)';
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

            // Always run BundleMaterialSeeder (it clears and re-seeds per bundle)
            Artisan::call('db:seed', ['--class' => 'BundleMaterialSeeder']);
            $results['bundle_materials'] = 'Seeded successfully';
            $results['bundle_items'] = DB::table('bundle_items')->count() . ' items';
            $results['custom_services'] = DB::table('custom_services')->count() . ' services';

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
     * Run BundleSeeder specifically.
     * By default replaces existing Inverter + Battery / Solar+Inverter+Battery bundles with the new data.
     * Use ?force=0 to skip if bundles already exist.
     */
    public function runBundleSeeder(Request $request)
    {
        try {
            $force = filter_var($request->query('force', 1), FILTER_VALIDATE_BOOLEAN);
            $bundleTypes = ['Inverter + Battery', 'Solar+Inverter+Battery'];

            $bundleCount = DB::table('bundles')->whereIn('bundle_type', $bundleTypes)->count();

            if ($bundleCount > 0 && !$force) {
                return ResponseHelper::success(
                    ['status' => 'Already seeded (skipped)', 'count' => $bundleCount, 'hint' => 'Add ?force=1 to replace with new bundle data'],
                    'Bundles already exist. Skipping seeder.'
                );
            }

            if ($force && $bundleCount > 0) {
                $bundleIds = DB::table('bundles')->whereIn('bundle_type', $bundleTypes)->pluck('id');
                DB::table('bundle_items')->whereIn('bundle_id', $bundleIds)->delete();
                DB::table('bundle_materials')->whereIn('bundle_id', $bundleIds)->delete();
                DB::table('custom_services')->whereIn('bundle_id', $bundleIds)->delete();
                DB::table('bundles')->whereIn('bundle_type', $bundleTypes)->delete();
            }

            Artisan::call('db:seed', ['--class' => 'BundleSeeder']);
            $newCount = DB::table('bundles')->whereIn('bundle_type', $bundleTypes)->count();

            if ($force && $newCount > 0) {
                Artisan::call('db:seed', ['--class' => 'BundleMaterialSeeder']);
            }

            return ResponseHelper::success(
                [
                    'status'           => $force ? 'Re-seeded (replaced)' : 'Seeded successfully',
                    'bundles'          => $newCount,
                    'bundle_items'     => DB::table('bundle_items')->count(),
                    'bundle_materials' => DB::table('bundle_materials')->count(),
                    'custom_services'  => DB::table('custom_services')->count(),
                ],
                'Bundle seeder executed successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to run bundle seeder: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Run BundleMaterialSeeder specifically.
     * Always re-seeds (clears old data per bundle and re-creates).
     */
    public function runBundleMaterialSeeder()
    {
        try {
            Artisan::call('db:seed', ['--class' => 'BundleMaterialSeeder']);

            $materialsCount = DB::table('bundle_materials')->count();
            $itemsCount     = DB::table('bundle_items')->count();
            $servicesCount  = DB::table('custom_services')->count();

            return ResponseHelper::success(
                [
                    'status'           => 'Seeded successfully',
                    'bundle_materials' => $materialsCount,
                    'bundle_items'     => $itemsCount,
                    'custom_services'  => $servicesCount,
                ],
                'Bundle material seeder executed successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to run bundle material seeder: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Run ProductSeeder specifically
     */
    public function runProductSeeder()
    {
        try {
            $productCount = DB::table('products')
                ->where('price', 1000)
                ->whereNotNull('featured_image')
                ->count();
            
            if ($productCount > 0) {
                return ResponseHelper::success(
                    ['status' => 'Already seeded (skipped)', 'count' => $productCount],
                    'Products from materials already exist. Skipping seeder.'
                );
            }

            Artisan::call('db:seed', ['--class' => 'ProductSeeder']);
            
            $newCount = DB::table('products')
                ->where('price', 1000)
                ->whereNotNull('featured_image')
                ->count();
            
            return ResponseHelper::success(
                ['status' => 'Seeded successfully', 'count' => $newCount],
                'Product seeder executed successfully'
            );
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to run product seeder: ' . $e->getMessage(), 500);
        }
    }
}
