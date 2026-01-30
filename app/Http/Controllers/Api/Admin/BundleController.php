<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BundleRequest;
use App\Models\Bundles;
use App\Models\BundleItems;
use App\Models\BundleCustomAppliance;
use App\Models\CustomService;
use App\Models\Product;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BundleController extends Controller
{


public function index(Request $request)
{
    try {
        $query = $request->query('q'); // accept ?q=1080 or any number

        // If no query parameter => return all bundles
        if (empty($query)) {
            $bundles = Bundles::with(['bundleItems.product', 'customServices'])->get();
            return ResponseHelper::success($bundles, 'Bundles fetched.');
        }

        // Ensure query is a number
        if (!is_numeric($query)) {
            return ResponseHelper::error('Query parameter q must be numeric.', 422);
        }

        $q = (float) $query;

        // Fetch all bundles with relations
        $bundles = Bundles::with(['bundleItems.product', 'customServices'])->get();

        if ($bundles->isEmpty()) {
            return ResponseHelper::error('No bundles found.', 404);
        }

        // Parse total_output to numeric (e.g. "3.8 kWh" -> 3.8, "1200 W" -> 1200); null/empty -> 0
        $parseOutput = function ($value) {
            if ($value === null || $value === '') {
                return 0.0;
            }
            if (is_numeric($value)) {
                return (float) $value;
            }
            if (preg_match('/^([\d.]+)/', (string) $value, $m)) {
                return (float) $m[1];
            }
            return 0.0;
        };

        // Find bundle with closest total_output to $q
        $closestBundle = $bundles->sortBy(function ($bundle) use ($q, $parseOutput) {
            $num = $parseOutput($bundle->total_output);
            return abs($num - $q);
        })->first();

        // If there's a bundle with total_output >= $q and closest to it
        $closerOrAbove = $bundles->filter(function ($bundle) use ($q, $parseOutput) {
            return $parseOutput($bundle->total_output) >= $q;
        })->sortBy(function ($bundle) use ($q, $parseOutput) {
            $num = $parseOutput($bundle->total_output);
            return abs($num - $q);
        })->first();

        return ResponseHelper::success(
            $closerOrAbove ?? $closestBundle,
            'Closest bundle fetched.'
        );
    } catch (Exception $e) {
        Log::error('Error fetching bundles: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);
        return ResponseHelper::error('Failed to fetch bundles.', 500);
    }
}


    public function store(BundleRequest $request)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $featuredImagePath = null;
        if ($request->hasFile('featured_image')) {
            $featuredImagePath = $request->file('featured_image')->store('bundles', 'public');
        }
            // Calculate total from products
            $totalProductPrice = 0;
            if (!empty($data['items'])) {
                $products = Product::whereIn('id', $data['items'])->get();
                $totalProductPrice = $products->sum('price');
            }

            // Calculate total from custom services
            $customServiceAmount = 0;
            if (!empty($data['custom_services'])) {
                foreach ($data['custom_services'] as $service) {
                    $customServiceAmount += $service['service_amount'] ?? 0;
                }
            }

            $calculatedTotal = $totalProductPrice + $customServiceAmount;

            $totalPrice = $data['total_price'] ?? $calculatedTotal;
            $discountPrice = $data['discount_price'] ?? ($totalPrice * 0.90); // 20% discount

            $bundle = Bundles::create([
                'title' => $data['title'] ?? null,
                'featured_image' => $featuredImagePath,
                'bundle_type' => $data['bundle_type'] ?? null,
                'product_model' => isset($data['product_model']) ? trim($data['product_model']) : null,
                'system_capacity_display' => isset($data['system_capacity_display']) ? trim($data['system_capacity_display']) : null,
                'detailed_description' => isset($data['detailed_description']) ? trim($data['detailed_description']) : null,
                'what_is_inside_bundle_text' => isset($data['what_is_inside_bundle_text']) ? trim($data['what_is_inside_bundle_text']) : null,
                'what_bundle_powers_text' => isset($data['what_bundle_powers_text']) ? trim($data['what_bundle_powers_text']) : null,
                'backup_time_description' => isset($data['backup_time_description']) ? trim($data['backup_time_description']) : null,
                'total_load' => isset($data['total_load']) ? trim($data['total_load']) : null,
                'inver_rating' => isset($data['inver_rating']) ? trim($data['inver_rating']) : null,
                'total_output' => isset($data['total_output']) ? trim($data['total_output']) : null,
                'total_price' => $totalPrice,
                'discount_price' => $discountPrice,
                'discount_end_date' => $data['discount_end_date'] ?? null,
            ]);

            if (!empty($data['custom_appliances'])) {
                foreach ($data['custom_appliances'] as $appliance) {
                    BundleCustomAppliance::create([
                        'bundle_id' => $bundle->id,
                        'name' => trim($appliance['name'] ?? ''),
                        'wattage' => (float) ($appliance['wattage'] ?? 0),
                        'quantity' => (int) ($appliance['quantity'] ?? 1),
                        'estimated_daily_hours_usage' => isset($appliance['estimated_daily_hours_usage']) ? (float) $appliance['estimated_daily_hours_usage'] : null,
                    ]);
                }
            }

            if (!empty($data['items'])) {
                foreach ($data['items'] as $productId) {
                    BundleItems::create([
                        'bundle_id' => $bundle->id,
                        'product_id' => $productId,
                    ]);
                }
            }

            if (!empty($data['custom_services'])) {
                foreach ($data['custom_services'] as $service) {
                    CustomService::create([
                        'bundle_id' => $bundle->id,
                        'title' => $service['title'] ?? null,
                        'service_amount' => $service['service_amount'] ?? 0,
                    ]);
                }
            }

            DB::commit();
            return ResponseHelper::success(
                $this->formatBundleResponse($bundle->load(['bundleItems.product.category', 'customServices', 'bundleMaterials.material.category', 'customAppliances'])),
                'Bundle created.',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating bundle: ' . $e->getMessage());
            return ResponseHelper::error('Failed to create bundle.', 500);
        }
    }

    public function show($id)
    {
        try {
            $bundle = Bundles::with([
                'bundleItems.product.category',
                'customServices',
                'bundleMaterials.material.category',
                'customAppliances',
            ])->find($id);
            
            if (!$bundle) {
                return ResponseHelper::error('Bundle not found.', 404);
            }

            // Format bundle items (from products)
            $bundleItems = $bundle->bundleItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product' => $item->product ? [
                        'id' => $item->product->id,
                        'title' => $item->product->title,
                        'price' => (float) ($item->product->price ?? 0),
                        'discount_price' => $item->product->discount_price ? (float) $item->product->discount_price : null,
                        'featured_image' => $item->product->featured_image_url ?? $item->product->featured_image ?? null,
                        'category' => $item->product->category ? [
                            'id' => $item->product->category->id,
                            'title' => $item->product->category->title,
                        ] : null,
                    ] : null,
                    'quantity' => $item->quantity ?? 1,
                ];
            });

            // Format bundle materials (from materials table)
            $bundleMaterials = $bundle->bundleMaterials->map(function ($bm) {
                return [
                    'id' => $bm->id,
                    'material_id' => $bm->material_id,
                    'material' => $bm->material ? [
                        'id' => $bm->material->id,
                        'name' => $bm->material->name,
                        'unit' => $bm->material->unit,
                        'rate' => (float) ($bm->material->rate ?? 0),
                        'selling_rate' => (float) ($bm->material->selling_rate ?? 0),
                        'warranty' => $bm->material->warranty,
                        'category' => $bm->material->category ? [
                            'id' => $bm->material->category->id,
                            'name' => $bm->material->category->name,
                            'code' => $bm->material->category->code,
                        ] : null,
                    ] : null,
                    'quantity' => (float) ($bm->quantity ?? 1),
                ];
            });

            // Format custom services
            $customServices = $bundle->customServices->map(function ($service) {
                return [
                    'id' => $service->id,
                    'title' => $service->title,
                    'service_amount' => (float) ($service->service_amount ?? 0),
                ];
            });

            // Format custom appliances
            $customAppliances = $bundle->customAppliances->map(function ($a) {
                return [
                    'id' => $a->id,
                    'bundle_id' => $a->bundle_id,
                    'name' => $a->name,
                    'wattage' => (float) $a->wattage,
                    'quantity' => (int) ($a->quantity ?? 1),
                    'estimated_daily_hours_usage' => $a->estimated_daily_hours_usage !== null ? (float) $a->estimated_daily_hours_usage : null,
                ];
            });

            // Build response
            $response = [
                'id' => $bundle->id,
                'title' => $bundle->title,
                'featured_image' => $bundle->featured_image,
                'bundle_type' => $bundle->bundle_type,
                'total_price' => (float) ($bundle->total_price ?? 0),
                'discount_price' => $bundle->discount_price ? (float) $bundle->discount_price : null,
                'discount_end_date' => $bundle->discount_end_date,
                'inver_rating' => $bundle->inver_rating,
                'total_output' => $bundle->total_output,
                'total_load' => $bundle->total_load,
                'product_model' => $bundle->product_model,
                'system_capacity_display' => $bundle->system_capacity_display,
                'detailed_description' => $bundle->detailed_description,
                'what_is_inside_bundle_text' => $bundle->what_is_inside_bundle_text,
                'what_bundle_powers_text' => $bundle->what_bundle_powers_text,
                'backup_time_description' => $bundle->backup_time_description,
                'created_at' => $bundle->created_at?->toIso8601String(),
                'updated_at' => $bundle->updated_at?->toIso8601String(),
                'featured_image_url' => $bundle->featured_image_url,
                'bundle_items' => $bundleItems,
                'bundle_materials' => $bundleMaterials,
                'custom_services' => $customServices,
                'custom_appliances' => $customAppliances,
            ];

            return ResponseHelper::success($response, 'Bundle found.');
        } catch (Exception $e) {
            Log::error('Error fetching bundle: ' . $e->getMessage());
            return ResponseHelper::error('Failed to fetch bundle.', 500);
        }
    }

    public function update(BundleRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $data = $request->validated();
            $bundle = Bundles::find($id);

            if (!$bundle) {
                return ResponseHelper::error('Bundle not found.', 404);
            }

             // Handle featured image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($bundle->featured_image && Storage::disk('public')->exists($bundle->featured_image)) {
                Storage::disk('public')->delete($bundle->featured_image);
            }

            // Store new image
            $path = $request->file('featured_image')->store('bundles', 'public');
            $data['featured_image'] = $path;
        } else {
            unset($data['featured_image']); // Prevent accidental overwrite with null
        }
            // Recalculate prices if not provided
            $totalProductPrice = 0;
            if (!empty($data['items'])) {
                $products = Product::whereIn('id', $data['items'])->get();
                $totalProductPrice = $products->sum('price');
            }

            $customServiceAmount = 0;
            if (!empty($data['custom_services'])) {
                foreach ($data['custom_services'] as $service) {
                    $customServiceAmount += $service['service_amount'] ?? 0;
                }
            }

            $calculatedTotal = $totalProductPrice + $customServiceAmount;

            $totalPrice = $data['total_price'] ?? $calculatedTotal;
            $discountPrice = $data['discount_price'] ?? ($totalPrice * 0.80);

            $bundle->update([
                'title' => $data['title'] ?? $bundle->title,
                'featured_image' => $data['featured_image'] ?? $bundle->featured_image,
                'bundle_type' => $data['bundle_type'] ?? $bundle->bundle_type,
                'product_model' => array_key_exists('product_model', $data) ? trim($data['product_model'] ?? '') : $bundle->product_model,
                'system_capacity_display' => array_key_exists('system_capacity_display', $data) ? trim($data['system_capacity_display'] ?? '') : $bundle->system_capacity_display,
                'detailed_description' => array_key_exists('detailed_description', $data) ? trim($data['detailed_description'] ?? '') : $bundle->detailed_description,
                'what_is_inside_bundle_text' => array_key_exists('what_is_inside_bundle_text', $data) ? trim($data['what_is_inside_bundle_text'] ?? '') : $bundle->what_is_inside_bundle_text,
                'what_bundle_powers_text' => array_key_exists('what_bundle_powers_text', $data) ? trim($data['what_bundle_powers_text'] ?? '') : $bundle->what_bundle_powers_text,
                'backup_time_description' => array_key_exists('backup_time_description', $data) ? trim($data['backup_time_description'] ?? '') : $bundle->backup_time_description,
                'total_load' => array_key_exists('total_load', $data) ? trim($data['total_load'] ?? '') : $bundle->total_load,
                'inver_rating' => array_key_exists('inver_rating', $data) ? trim($data['inver_rating'] ?? '') : $bundle->inver_rating,
                'total_output' => array_key_exists('total_output', $data) ? trim($data['total_output'] ?? '') : $bundle->total_output,
                'total_price' => $totalPrice,
                'discount_price' => $discountPrice,
                'discount_end_date' => $data['discount_end_date'] ?? $bundle->discount_end_date,
            ]);

            if (array_key_exists('custom_appliances', $data)) {
                BundleCustomAppliance::where('bundle_id', $bundle->id)->delete();
                if (!empty($data['custom_appliances'])) {
                    foreach ($data['custom_appliances'] as $appliance) {
                        BundleCustomAppliance::create([
                            'bundle_id' => $bundle->id,
                            'name' => trim($appliance['name'] ?? ''),
                            'wattage' => (float) ($appliance['wattage'] ?? 0),
                            'quantity' => (int) ($appliance['quantity'] ?? 1),
                            'estimated_daily_hours_usage' => isset($appliance['estimated_daily_hours_usage']) ? (float) $appliance['estimated_daily_hours_usage'] : null,
                        ]);
                    }
                }
            }

            if (isset($data['items'])) {
                BundleItems::where('bundle_id', $bundle->id)->delete();
                foreach ($data['items'] as $productId) {
                    BundleItems::create([
                        'bundle_id' => $bundle->id,
                        'product_id' => $productId,
                    ]);
                }
            }

            if (isset($data['custom_services'])) {
                CustomService::where('bundle_id', $bundle->id)->delete();
                foreach ($data['custom_services'] as $service) {
                    CustomService::create([
                        'bundle_id' => $bundle->id,
                        'title' => $service['title'] ?? null,
                        'service_amount' => $service['service_amount'] ?? 0,
                    ]);
                }
            }

            DB::commit();
            $bundle->loadMissing(['bundleItems.product.category', 'customServices', 'bundleMaterials.material.category', 'customAppliances']);

            return ResponseHelper::success(
                $this->formatBundleResponse($bundle),
                'Bundle updated successfully.'
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating bundle: ' . $e->getMessage());
            return ResponseHelper::error('Failed to update bundle.', 500);
        }
    }

    /**
     * Format bundle for API response (single bundle with all relations).
     */
    private function formatBundleResponse(Bundles $bundle): array
    {
        $bundleItems = $bundle->bundleItems->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product' => $item->product ? [
                    'id' => $item->product->id,
                    'title' => $item->product->title,
                    'price' => (float) ($item->product->price ?? 0),
                    'discount_price' => $item->product->discount_price ? (float) $item->product->discount_price : null,
                    'featured_image' => $item->product->featured_image_url ?? $item->product->featured_image ?? null,
                    'category' => $item->product->category ? ['id' => $item->product->category->id, 'title' => $item->product->category->title] : null,
                ] : null,
                'quantity' => $item->quantity ?? 1,
            ];
        });
        $bundleMaterials = $bundle->bundleMaterials->map(function ($bm) {
            return [
                'id' => $bm->id,
                'material_id' => $bm->material_id,
                'material' => $bm->material ? [
                    'id' => $bm->material->id,
                    'name' => $bm->material->name,
                    'unit' => $bm->material->unit,
                    'rate' => (float) ($bm->material->rate ?? 0),
                    'selling_rate' => (float) ($bm->material->selling_rate ?? 0),
                    'warranty' => $bm->material->warranty,
                    'category' => $bm->material->category ? ['id' => $bm->material->category->id, 'name' => $bm->material->category->name, 'code' => $bm->material->category->code] : null,
                ] : null,
                'quantity' => (float) ($bm->quantity ?? 1),
            ];
        });
        $customServices = $bundle->customServices->map(function ($s) {
            return ['id' => $s->id, 'title' => $s->title, 'service_amount' => (float) ($s->service_amount ?? 0)];
        });
        $customAppliances = $bundle->customAppliances->map(function ($a) {
            return [
                'id' => $a->id,
                'bundle_id' => $a->bundle_id,
                'name' => $a->name,
                'wattage' => (float) $a->wattage,
                'quantity' => (int) ($a->quantity ?? 1),
                'estimated_daily_hours_usage' => $a->estimated_daily_hours_usage !== null ? (float) $a->estimated_daily_hours_usage : null,
            ];
        });
        return [
            'id' => $bundle->id,
            'title' => $bundle->title,
            'featured_image' => $bundle->featured_image,
            'featured_image_url' => $bundle->featured_image_url,
            'bundle_type' => $bundle->bundle_type,
            'total_price' => (float) ($bundle->total_price ?? 0),
            'discount_price' => $bundle->discount_price ? (float) $bundle->discount_price : null,
            'discount_end_date' => $bundle->discount_end_date,
            'inver_rating' => $bundle->inver_rating,
            'total_output' => $bundle->total_output,
            'total_load' => $bundle->total_load,
            'product_model' => $bundle->product_model,
            'system_capacity_display' => $bundle->system_capacity_display,
            'detailed_description' => $bundle->detailed_description,
            'what_is_inside_bundle_text' => $bundle->what_is_inside_bundle_text,
            'what_bundle_powers_text' => $bundle->what_bundle_powers_text,
            'backup_time_description' => $bundle->backup_time_description,
            'created_at' => $bundle->created_at?->toIso8601String(),
            'updated_at' => $bundle->updated_at?->toIso8601String(),
            'bundle_items' => $bundleItems,
            'bundle_materials' => $bundleMaterials,
            'custom_services' => $customServices,
            'custom_appliances' => $customAppliances,
        ];
    }

    public function destroy($id)
    {
        try {
            $bundle = Bundles::find($id);
            if (!$bundle) {
                return ResponseHelper::error('Bundle not found.', 404);
            }

            $bundle->delete();
            return ResponseHelper::success(null, 'Bundle deleted.');
        } catch (Exception $e) {
            Log::error('Error deleting bundle: ' . $e->getMessage());
            return ResponseHelper::error('Failed to delete bundle.', 500);
        }
    }
}