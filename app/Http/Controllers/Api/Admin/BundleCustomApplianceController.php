<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Bundles;
use App\Models\BundleCustomAppliance;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BundleCustomApplianceController extends Controller
{
    /**
     * GET /api/bundles/{bundleId}/custom-appliances
     */
    public function index($bundleId)
    {
        try {
            $bundle = Bundles::findOrFail($bundleId);
            $appliances = $bundle->customAppliances()->get()->map(function ($a) {
                return [
                    'id' => $a->id,
                    'bundle_id' => $a->bundle_id,
                    'name' => $a->name,
                    'wattage' => (float) $a->wattage,
                    'quantity' => (int) ($a->quantity ?? 1),
                    'estimated_daily_hours_usage' => $a->estimated_daily_hours_usage !== null ? (float) $a->estimated_daily_hours_usage : null,
                ];
            });
            return ResponseHelper::success($appliances, 'Custom appliances fetched.');
        } catch (Exception $e) {
            Log::error('Error fetching custom appliances: ' . $e->getMessage());
            return ResponseHelper::error('Failed to fetch custom appliances.', 500);
        }
    }

    /**
     * POST /api/bundles/{bundleId}/custom-appliances
     */
    public function store(Request $request, $bundleId)
    {
        try {
            $bundle = Bundles::findOrFail($bundleId);
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'wattage' => 'required|numeric|min:0',
                'quantity' => 'nullable|integer|min:1',
                'estimated_daily_hours_usage' => 'nullable|numeric|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $data = $validator->validated();
            $appliance = BundleCustomAppliance::create([
                'bundle_id' => $bundle->id,
                'name' => trim($data['name']),
                'wattage' => (float) $data['wattage'],
                'quantity' => (int) ($data['quantity'] ?? 1),
                'estimated_daily_hours_usage' => isset($data['estimated_daily_hours_usage']) ? (float) $data['estimated_daily_hours_usage'] : null,
            ]);
            return ResponseHelper::success([
                'id' => $appliance->id,
                'bundle_id' => $appliance->bundle_id,
                'name' => $appliance->name,
                'wattage' => (float) $appliance->wattage,
                'quantity' => (int) ($appliance->quantity ?? 1),
                'estimated_daily_hours_usage' => $appliance->estimated_daily_hours_usage !== null ? (float) $appliance->estimated_daily_hours_usage : null,
            ], 'Custom appliance created.', 201);
        } catch (Exception $e) {
            Log::error('Error creating custom appliance: ' . $e->getMessage());
            return ResponseHelper::error('Failed to create custom appliance.', 500);
        }
    }

    /**
     * PUT /api/bundles/{bundleId}/custom-appliances/{id}
     */
    public function update(Request $request, $bundleId, $id)
    {
        try {
            $appliance = BundleCustomAppliance::where('bundle_id', $bundleId)->findOrFail($id);
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'wattage' => 'sometimes|required|numeric|min:0',
                'quantity' => 'nullable|integer|min:1',
                'estimated_daily_hours_usage' => 'nullable|numeric|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }
            $data = $validator->validated();
            if (isset($data['name'])) $appliance->name = trim($data['name']);
            if (isset($data['wattage'])) $appliance->wattage = (float) $data['wattage'];
            if (array_key_exists('quantity', $data)) $appliance->quantity = (int) ($data['quantity'] ?? 1);
            if (array_key_exists('estimated_daily_hours_usage', $data)) $appliance->estimated_daily_hours_usage = $data['estimated_daily_hours_usage'] !== null ? (float) $data['estimated_daily_hours_usage'] : null;
            $appliance->save();
            return ResponseHelper::success([
                'id' => $appliance->id,
                'bundle_id' => $appliance->bundle_id,
                'name' => $appliance->name,
                'wattage' => (float) $appliance->wattage,
                'quantity' => (int) ($appliance->quantity ?? 1),
                'estimated_daily_hours_usage' => $appliance->estimated_daily_hours_usage !== null ? (float) $appliance->estimated_daily_hours_usage : null,
            ], 'Custom appliance updated.');
        } catch (Exception $e) {
            Log::error('Error updating custom appliance: ' . $e->getMessage());
            return ResponseHelper::error('Failed to update custom appliance.', 500);
        }
    }

    /**
     * DELETE /api/bundles/{bundleId}/custom-appliances/{id}
     */
    public function destroy($bundleId, $id)
    {
        try {
            $appliance = BundleCustomAppliance::where('bundle_id', $bundleId)->findOrFail($id);
            $appliance->delete();
            return ResponseHelper::success(null, 'Custom appliance deleted.');
        } catch (Exception $e) {
            Log::error('Error deleting custom appliance: ' . $e->getMessage());
            return ResponseHelper::error('Failed to delete custom appliance.', 500);
        }
    }
}
