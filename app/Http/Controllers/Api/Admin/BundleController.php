<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BundleRequest;
use App\Models\Bundles;
use App\Models\BundleItems;
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

        // Find bundle with closest total_output to $q
        $closestBundle = $bundles->sortBy(function ($bundle) use ($q) {
            return abs($bundle->total_output - $q);
        })->first();

        // If there’s a bundle with total_output >= $q and closest to it
        $closerOrAbove = $bundles
            ->where('total_output', '>=', $q)
            ->sortBy(function ($bundle) use ($q) {
                return abs($bundle->total_output - $q);
            })
            ->first();

        return ResponseHelper::success(
            $closerOrAbove ?? $closestBundle,
            'Closest bundle fetched.'
        );
    } catch (Exception $e) {
        Log::error('Error fetching bundles: ' . $e->getMessage());
        return ResponseHelper::error('Failed to fetch bundles.', 500, $e->getMessage());
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
                'total_price' => $totalPrice,
                'discount_price' => $discountPrice,
                'discount_end_date' => $data['discount_end_date'] ?? null,
            ]);

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
                $bundle->load(['bundleItems', 'customServices']),
                'Bundle created.',
                201
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating bundle: ' . $e->getMessage());
            return ResponseHelper::error('Failed to create bundle.', 500, $e->getMessage());
        }
    }

    public function show($id)
    {
        try {
            $bundle = Bundles::with(['bundleItems.product', 'customServices'])->find($id);
            if (!$bundle) {
                return ResponseHelper::error('Bundle not found.', 404);
            }

            return ResponseHelper::success($bundle, 'Bundle found.');
        } catch (Exception $e) {
            Log::error('Error fetching bundle: ' . $e->getMessage());
            return ResponseHelper::error('Failed to fetch bundle.', 500, $e->getMessage());
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
                'total_price' => $totalPrice,
                'discount_price' => $discountPrice,
                'discount_end_date' => $data['discount_end_date'] ?? $bundle->discount_end_date,
            ]);

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
            $bundle->loadMissing(['bundleItems', 'customServices']);

            return ResponseHelper::success($bundle, 'Bundle updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating bundle: ' . $e->getMessage());
            return ResponseHelper::error('Failed to update bundle.', 500, $e->getMessage());
        }
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
            return ResponseHelper::error('Failed to delete bundle.', 500, $e->getMessage());
        }
    }
}