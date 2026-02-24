<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Bundles;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BundleController extends Controller
{
    /**
     * Parse load/rating string to numeric in watts.
     * Handles: "1200 W" -> 1200, "3.8 kW" -> 3800, "1.2 kVA" -> 1200, "1.2" -> 1200.
     */
    private function parseLoadToWatts($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $valueStr = trim((string) $value);
        $numericValue = 0.0;

        // Extract numeric value
        $normalized = preg_replace('/[,\s]+/', '', $valueStr);
        if ($normalized !== null && is_numeric($normalized)) {
            $numericValue = (float) $normalized;
        } elseif (preg_match('/([\d.]+)/', $valueStr, $m)) {
            $numericValue = (float) $m[1];
        } else {
            return 0.0;
        }

        // Check for unit indicators and convert to watts
        $lowerValue = strtolower($valueStr);
        if (strpos($lowerValue, 'kva') !== false || strpos($lowerValue, 'kw') !== false || strpos($lowerValue, 'kwh') !== false) {
            // Value is in kW, convert to watts
            return $numericValue * 1000;
        } elseif (strpos($lowerValue, 'w') !== false) {
            // Value is already in watts
            return $numericValue;
        } else {
            // No unit specified - assume kW if value < 1000, otherwise assume watts
            // But based on your data (0.6, 1.2), these are likely kW
            if ($numericValue < 100) {
                // Likely kW (e.g., 0.6, 1.2, 3.8)
                return $numericValue * 1000;
            } else {
                // Likely already in watts
                return $numericValue;
            }
        }
    }

    /**
     * Resolve bundle usable capacity in watts.
     * Prefer total_load, fallback to inverter rating when total_load is missing/zero.
     */
    private function resolveBundleCapacityWatts($bundle): float
    {
        $fromTotalLoad = $this->parseLoadToWatts($bundle->total_load ?? null);
        if ($fromTotalLoad > 0) {
            return $fromTotalLoad;
        }

        return $this->parseLoadToWatts($bundle->inver_rating ?? null);
    }

    /**
     * GET /api/bundles
     * Get all active bundles (Public endpoint for Buy Now flow).
     * When ?q={wattage} is present: return bundles that can handle load with 30% headroom
     * (i.e. bundles where total_load >= q * 1.30). If none, returns best-effort (highest capacity first).
     */
    public function index(Request $request)
    {
        try {
            $query = $request->query('q');
            $bundlesQuery = Bundles::with(['bundleItems.product', 'customServices', 'bundleMaterials.material.category'])
                ->orderBy('created_at', 'desc');
            $bundles = $bundlesQuery->get();

            $minCapacityWatts = null;

            // When q is provided (load in watts), filter by 30% headroom: require total_load >= q * 1.30
            if ($query !== null && $query !== '' && is_numeric($query)) {
                $q = (float) $query; // q is in watts
                $minCapacityWatts = (float) round($q * 1.30, 2); // 30% above passed wattage

                $bundles = $bundles->map(function ($bundle) {
                    // Convert configured capacity to watts for comparison (total_load or inverter rating)
                    $bundle->_parsed_load_watts = $this->resolveBundleCapacityWatts($bundle);
                    return $bundle;
                });

                $matching = $bundles->filter(function ($bundle) use ($minCapacityWatts) {
                    return $bundle->_parsed_load_watts >= $minCapacityWatts;
                })->values();

                // If none meet 30% headroom, return all sorted by total_load desc (best effort)
                if ($matching->isEmpty()) {
                    $bundles = $bundles->sortByDesc('_parsed_load_watts')->values();
                } else {
                    $bundles = $matching->sortBy('_parsed_load_watts')->values(); // smallest adequate first
                }
            }

            $mapped = $bundles->map(function ($bundle) {
                $item = [
                    'id' => $bundle->id,
                    'title' => $bundle->title,
                    'bundle_type' => $bundle->bundle_type ?? null,
                    'featured_image' => $bundle->featured_image_url ?? $bundle->featured_image ?? null,
                    'total_price' => (float) ($bundle->total_price ?? 0),
                    'discount_price' => $bundle->discount_price ? (float) $bundle->discount_price : null,
                    'description' => $bundle->description ?? $bundle->detailed_description ?? null,
                    'is_active' => true,
                    'total_load' => $bundle->total_load,
                    'total_output' => $bundle->total_output,
                    'inver_rating' => $bundle->inver_rating,
                    'created_at' => $bundle->created_at?->toIso8601String(),
                    'updated_at' => $bundle->updated_at?->toIso8601String(),
                ];
                // Remove temporary attribute if present
                if (isset($bundle->_parsed_load_watts)) {
                    unset($bundle->_parsed_load_watts);
                }
                return $item;
            });

            $payload = $mapped->values()->all();

            return ResponseHelper::success($payload, 'Bundles retrieved successfully');
        } catch (Exception $e) {
            Log::error('Error fetching bundles: ' . $e->getMessage());
            return ResponseHelper::error('Failed to fetch bundles', 500);
        }
    }
}
