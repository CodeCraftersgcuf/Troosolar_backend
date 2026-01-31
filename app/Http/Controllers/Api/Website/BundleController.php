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
     * Parse load/wattage string to numeric (e.g. "1200 W" -> 1200, "3.8 kWh" -> 3.8).
     */
    private function parseLoadToNumber($value): float
    {
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
                $q = (float) $query;
                $minCapacityWatts = (float) round($q * 1.30, 2); // 30% above passed wattage

                $bundles = $bundles->map(function ($bundle) {
                    $bundle->_parsed_load = $this->parseLoadToNumber($bundle->total_load);
                    return $bundle;
                });

                $matching = $bundles->filter(function ($bundle) use ($minCapacityWatts) {
                    return $bundle->_parsed_load >= $minCapacityWatts;
                })->values();

                // If none meet 30% headroom, return all sorted by total_load desc (best effort)
                if ($matching->isEmpty()) {
                    $bundles = $bundles->sortByDesc('_parsed_load')->values();
                } else {
                    $bundles = $matching->sortBy('_parsed_load')->values(); // smallest adequate first
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
                if (isset($bundle->_parsed_load)) {
                    unset($bundle->_parsed_load);
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
