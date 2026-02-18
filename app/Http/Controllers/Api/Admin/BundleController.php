<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BundleRequest;
use App\Models\Bundles;
use App\Models\BundleItems;
use App\Models\BundleMaterial;
use App\Models\BundleCustomAppliance;
use App\Models\CustomService;
use App\Models\Product;
use App\Helpers\ResponseHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
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
            $bundles = Bundles::with(['bundleItems.product', 'customServices', 'brand'])->get();
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

            $safeTrim = function ($v) {
                if ($v === null || $v === '') return $v === '' ? '' : null;
                return is_string($v) ? trim($v) : $v;
            };

            $createData = [
                'title' => $safeTrim($data['title'] ?? null),
                'total_price' => $totalPrice,
                'discount_price' => $discountPrice,
                'discount_end_date' => isset($data['discount_end_date']) && $data['discount_end_date'] !== '' ? $data['discount_end_date'] : null,
            ];
            if (Schema::hasColumn('bundles', 'brand_id')) {
                $createData['brand_id'] = isset($data['brand_id']) && $data['brand_id'] !== '' ? (int) $data['brand_id'] : null;
            }
            if (Schema::hasColumn('bundles', 'featured_image')) {
                $createData['featured_image'] = $featuredImagePath;
            }
            if (Schema::hasColumn('bundles', 'bundle_type')) {
                $createData['bundle_type'] = $safeTrim($data['bundle_type'] ?? null);
            }

            if (Schema::hasColumn('bundles', 'product_model')) {
                $createData['product_model'] = $safeTrim($data['product_model'] ?? null);
            }
            if (Schema::hasColumn('bundles', 'system_capacity_display')) {
                $createData['system_capacity_display'] = $safeTrim($data['system_capacity_display'] ?? null);
            }
            if (Schema::hasColumn('bundles', 'detailed_description')) {
                $createData['detailed_description'] = $safeTrim($data['detailed_description'] ?? null);
            }
            if (Schema::hasColumn('bundles', 'what_is_inside_bundle_text')) {
                $createData['what_is_inside_bundle_text'] = $safeTrim($data['what_is_inside_bundle_text'] ?? null);
            }
            if (Schema::hasColumn('bundles', 'what_bundle_powers_text')) {
                $createData['what_bundle_powers_text'] = $safeTrim($data['what_bundle_powers_text'] ?? null);
            }
            if (Schema::hasColumn('bundles', 'backup_time_description')) {
                $createData['backup_time_description'] = $safeTrim($data['backup_time_description'] ?? null);
            }
            if (Schema::hasColumn('bundles', 'total_load')) {
                $createData['total_load'] = $safeTrim($data['total_load'] ?? null);
            }
            if (Schema::hasColumn('bundles', 'inver_rating')) {
                $createData['inver_rating'] = $safeTrim($data['inver_rating'] ?? null);
            }
            if (Schema::hasColumn('bundles', 'total_output')) {
                $createData['total_output'] = $safeTrim($data['total_output'] ?? null);
            }
            if (Schema::hasColumn('bundles', 'specifications') && array_key_exists('specifications', $data)) {
                $createData['specifications'] = is_array($data['specifications']) ? $data['specifications'] : null;
            }

            $bundle = Bundles::create($createData);

            if (!empty($data['custom_appliances']) && Schema::hasTable('bundle_custom_appliances')) {
                foreach ($data['custom_appliances'] as $appliance) {
                    $name = isset($appliance['name']) && is_string($appliance['name']) ? trim($appliance['name']) : '';
                    BundleCustomAppliance::create([
                        'bundle_id' => $bundle->id,
                        'name' => $name,
                        'wattage' => (float) ($appliance['wattage'] ?? 0),
                        'quantity' => (int) ($appliance['quantity'] ?? 1),
                        'estimated_daily_hours_usage' => isset($appliance['estimated_daily_hours_usage']) ? (float) $appliance['estimated_daily_hours_usage'] : null,
                    ]);
                }
            }

            if (!empty($data['items_detail'])) {
                foreach ($data['items_detail'] as $itemDetail) {
                    BundleItems::create([
                        'bundle_id'     => $bundle->id,
                        'product_id'    => $itemDetail['product_id'],
                        'quantity'      => $itemDetail['quantity'] ?? 1,
                        'rate_override' => isset($itemDetail['rate_override']) && $itemDetail['rate_override'] !== '' ? $itemDetail['rate_override'] : null,
                    ]);
                }
            } elseif (!empty($data['items'])) {
                foreach ($data['items'] as $productId) {
                    BundleItems::create([
                        'bundle_id'  => $bundle->id,
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
            $relations = ['bundleItems.product.category', 'customServices', 'bundleMaterials.material.category', 'brand'];
            if (Schema::hasTable('bundle_custom_appliances')) {
                $relations[] = 'customAppliances';
            }
            return ResponseHelper::success(
                $this->formatBundleResponse($bundle->load($relations)),
                'Bundle created.',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating bundle: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
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
                'brand',
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
                    'rate_override' => $item->rate_override !== null ? (float) $item->rate_override : null,
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
                    'rate_override' => $bm->rate_override !== null ? (float) $bm->rate_override : null,
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
                'brand_id' => $bundle->brand_id,
                'brand' => $bundle->brand ? ['id' => $bundle->brand->id, 'title' => $bundle->brand->title] : null,
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
                'specifications' => $bundle->specifications ?? null,
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

            $updatePayload = [
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
            ];
            if (Schema::hasColumn('bundles', 'brand_id')) {
                $updatePayload['brand_id'] = array_key_exists('brand_id', $data)
                    ? ($data['brand_id'] === '' || $data['brand_id'] === null ? null : (int) $data['brand_id'])
                    : $bundle->brand_id;
            }
            $bundle->update($updatePayload);
            if (Schema::hasColumn('bundles', 'specifications') && array_key_exists('specifications', $data)) {
                $bundle->specifications = is_array($data['specifications']) ? $data['specifications'] : null;
                $bundle->save();
            }

            if (array_key_exists('custom_appliances', $data) && Schema::hasTable('bundle_custom_appliances')) {
                BundleCustomAppliance::where('bundle_id', $bundle->id)->delete();
                if (!empty($data['custom_appliances'])) {
                    foreach ($data['custom_appliances'] as $appliance) {
                        $name = isset($appliance['name']) && is_string($appliance['name']) ? trim($appliance['name']) : '';
                        BundleCustomAppliance::create([
                            'bundle_id' => $bundle->id,
                            'name' => $name,
                            'wattage' => (float) ($appliance['wattage'] ?? 0),
                            'quantity' => (int) ($appliance['quantity'] ?? 1),
                            'estimated_daily_hours_usage' => isset($appliance['estimated_daily_hours_usage']) ? (float) $appliance['estimated_daily_hours_usage'] : null,
                        ]);
                    }
                }
            }

            if (isset($data['items_detail'])) {
                BundleItems::where('bundle_id', $bundle->id)->delete();
                foreach ($data['items_detail'] as $itemDetail) {
                    BundleItems::create([
                        'bundle_id'     => $bundle->id,
                        'product_id'    => $itemDetail['product_id'],
                        'quantity'      => $itemDetail['quantity'] ?? 1,
                        'rate_override' => isset($itemDetail['rate_override']) && $itemDetail['rate_override'] !== '' ? $itemDetail['rate_override'] : null,
                    ]);
                }
            } elseif (isset($data['items'])) {
                BundleItems::where('bundle_id', $bundle->id)->delete();
                foreach ($data['items'] as $productId) {
                    BundleItems::create([
                        'bundle_id'  => $bundle->id,
                        'product_id' => $productId,
                    ]);
                }
            }

            if (isset($data['materials_detail'])) {
                BundleMaterial::where('bundle_id', $bundle->id)->delete();
                foreach ($data['materials_detail'] as $matDetail) {
                    BundleMaterial::create([
                        'bundle_id'     => $bundle->id,
                        'material_id'   => $matDetail['material_id'],
                        'quantity'      => $matDetail['quantity'] ?? 1,
                        'rate_override' => isset($matDetail['rate_override']) && $matDetail['rate_override'] !== '' ? $matDetail['rate_override'] : null,
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
            $updateRelations = ['bundleItems.product.category', 'customServices', 'bundleMaterials.material.category', 'brand'];
            if (Schema::hasTable('bundle_custom_appliances')) {
                $updateRelations[] = 'customAppliances';
            }
            $bundle->loadMissing($updateRelations);

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
                'rate_override' => $item->rate_override !== null ? (float) $item->rate_override : null,
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
                'rate_override' => $bm->rate_override !== null ? (float) $bm->rate_override : null,
            ];
        });
        $customServices = $bundle->customServices->map(function ($s) {
            return ['id' => $s->id, 'title' => $s->title, 'service_amount' => (float) ($s->service_amount ?? 0)];
        });
        $customAppliances = Schema::hasTable('bundle_custom_appliances') && $bundle->relationLoaded('customAppliances')
            ? $bundle->customAppliances->map(function ($a) {
                return [
                    'id' => $a->id,
                    'bundle_id' => $a->bundle_id,
                    'name' => $a->name,
                    'wattage' => (float) $a->wattage,
                    'quantity' => (int) ($a->quantity ?? 1),
                    'estimated_daily_hours_usage' => $a->estimated_daily_hours_usage !== null ? (float) $a->estimated_daily_hours_usage : null,
                ];
            })
            : collect([]);
        return [
            'id' => $bundle->id,
            'brand_id' => $bundle->brand_id ?? null,
            'brand' => $bundle->relationLoaded('brand') && $bundle->brand
                ? ['id' => $bundle->brand->id, 'title' => $bundle->brand->title]
                : null,
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
            'specifications' => $bundle->specifications ?? null,
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