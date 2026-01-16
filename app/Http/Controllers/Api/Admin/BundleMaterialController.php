<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\BundleMaterial;
use App\Models\Bundles;
use App\Models\Material;
use App\Helpers\ResponseHelper;
use Illuminate\Http\Request;
use Exception;

class BundleMaterialController extends Controller
{
    /**
     * Get all materials for a bundle
     */
    public function index($bundleId)
    {
        try {
            $bundle = Bundles::find($bundleId);
            if (!$bundle) {
                return ResponseHelper::error('Bundle not found.', 404);
            }

            $materials = BundleMaterial::where('bundle_id', $bundleId)
                ->with('material.category')
                ->get();

            return ResponseHelper::success($materials, 'Bundle materials fetched successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch bundle materials.', 500);
        }
    }

    /**
     * Add material to bundle
     */
    public function store(Request $request, $bundleId)
    {
        try {
            $bundle = Bundles::find($bundleId);
            if (!$bundle) {
                return ResponseHelper::error('Bundle not found.', 404);
            }

            $validated = $request->validate([
                'material_id' => 'required|exists:materials,id',
                'quantity' => 'required|numeric|min:0.01',
            ]);

            // Check if material already exists in bundle
            $existing = BundleMaterial::where('bundle_id', $bundleId)
                ->where('material_id', $validated['material_id'])
                ->first();

            if ($existing) {
                return ResponseHelper::error('Material already exists in this bundle. Use update instead.', 400);
            }

            $bundleMaterial = BundleMaterial::create([
                'bundle_id' => $bundleId,
                'material_id' => $validated['material_id'],
                'quantity' => $validated['quantity'],
            ]);

            return ResponseHelper::success(
                $bundleMaterial->load('material.category'),
                'Material added to bundle successfully.',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to add material to bundle.', 500);
        }
    }

    /**
     * Update material quantity in bundle
     */
    public function update(Request $request, $bundleId, $id)
    {
        try {
            $bundleMaterial = BundleMaterial::where('bundle_id', $bundleId)
                ->where('id', $id)
                ->first();

            if (!$bundleMaterial) {
                return ResponseHelper::error('Bundle material not found.', 404);
            }

            $validated = $request->validate([
                'quantity' => 'required|numeric|min:0.01',
            ]);

            $bundleMaterial->update(['quantity' => $validated['quantity']]);

            return ResponseHelper::success(
                $bundleMaterial->fresh()->load('material.category'),
                'Bundle material updated successfully.'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to update bundle material.', 500);
        }
    }

    /**
     * Remove material from bundle
     */
    public function destroy($bundleId, $id)
    {
        try {
            $bundleMaterial = BundleMaterial::where('bundle_id', $bundleId)
                ->where('id', $id)
                ->first();

            if (!$bundleMaterial) {
                return ResponseHelper::error('Bundle material not found.', 404);
            }

            $bundleMaterial->delete();

            return ResponseHelper::success(null, 'Material removed from bundle successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to remove material from bundle.', 500);
        }
    }

    /**
     * Bulk add materials to bundle
     */
    public function bulkStore(Request $request, $bundleId)
    {
        try {
            $bundle = Bundles::find($bundleId);
            if (!$bundle) {
                return ResponseHelper::error('Bundle not found.', 404);
            }

            $validated = $request->validate([
                'materials' => 'required|array',
                'materials.*.material_id' => 'required|exists:materials,id',
                'materials.*.quantity' => 'required|numeric|min:0.01',
            ]);

            $created = [];
            $errors = [];

            foreach ($validated['materials'] as $mat) {
                try {
                    // Check if already exists
                    $existing = BundleMaterial::where('bundle_id', $bundleId)
                        ->where('material_id', $mat['material_id'])
                        ->first();

                    if ($existing) {
                        $errors[] = "Material ID {$mat['material_id']} already exists in bundle";
                        continue;
                    }

                    $bundleMaterial = BundleMaterial::create([
                        'bundle_id' => $bundleId,
                        'material_id' => $mat['material_id'],
                        'quantity' => $mat['quantity'],
                    ]);

                    $created[] = $bundleMaterial->load('material.category');
                } catch (Exception $e) {
                    $errors[] = "Failed to add material ID {$mat['material_id']}: " . $e->getMessage();
                }
            }

            return ResponseHelper::success([
                'created' => $created,
                'errors' => $errors,
            ], 'Bulk operation completed.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to bulk add materials.', 500);
        }
    }
}
