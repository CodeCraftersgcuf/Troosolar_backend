<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Bundles;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
     * For recommendation by inverter demand, prefer inverter rating first.
     * Fallback to total_load only when inverter rating is missing/zero.
     */
    private function resolveBundleCapacityWatts($bundle): float
    {
        $fromInverterRating = $this->parseLoadToWatts($bundle->inver_rating ?? null);
        if ($fromInverterRating > 0) {
            return $fromInverterRating;
        }

        return $this->parseLoadToWatts($bundle->total_load ?? null);
    }

    /**
     * GET /api/bundles
     * Get all active bundles (Public endpoint for Buy Now flow).
     * When ?q={wattage} is present: treat q as target inverter capacity (in watts),
     * return exact-capacity matches first; if none, return closest-capacity bundles.
     */
    public function index(Request $request)
    {
        try {
            $query = $request->query('q');
            $bundlesQuery = Bundles::with(['bundleItems.product', 'customServices', 'bundleMaterials.material.category'])
                ->orderBy('created_at', 'desc');
            if (Schema::hasColumn('bundles', 'is_available')) {
                $bundlesQuery->where('is_available', true);
            }
            $bundles = $bundlesQuery->get();

            // When q is provided (target capacity in watts), find exact/closest capacity bundles.
            if ($query !== null && $query !== '' && is_numeric($query)) {
                $targetCapacityWatts = (float) $query;

                $bundles = $bundles->map(function ($bundle) {
                    // Convert configured capacity to watts for comparison (total_load or inverter rating)
                    $bundle->_parsed_load_watts = $this->resolveBundleCapacityWatts($bundle);
                    return $bundle;
                });

                // Prefer exact match first (allow tiny float tolerance).
                $exact = $bundles->filter(function ($bundle) use ($targetCapacityWatts) {
                    return abs(((float) ($bundle->_parsed_load_watts ?? 0)) - $targetCapacityWatts) < 0.0001;
                })->values();

                if (!$exact->isEmpty()) {
                    $bundles = $exact->sortBy('_parsed_load_watts')->values();
                } else {
                    // Prefer the closest bundle(s) at or above requested capacity.
                    $aboveOrEqual = $bundles
                        ->filter(function ($bundle) use ($targetCapacityWatts) {
                            return ((float) ($bundle->_parsed_load_watts ?? 0)) >= $targetCapacityWatts;
                        })
                        ->values();

                    if (!$aboveOrEqual->isEmpty()) {
                        $minAboveDelta = $aboveOrEqual
                            ->map(function ($bundle) use ($targetCapacityWatts) {
                                return ((float) ($bundle->_parsed_load_watts ?? 0)) - $targetCapacityWatts;
                            })
                            ->min();

                        $bundles = $aboveOrEqual
                            ->filter(function ($bundle) use ($targetCapacityWatts, $minAboveDelta) {
                                $deltaAbove = ((float) ($bundle->_parsed_load_watts ?? 0)) - $targetCapacityWatts;
                                return abs($deltaAbove - $minAboveDelta) < 0.0001;
                            })
                            ->sortBy('_parsed_load_watts')
                            ->values();
                    } else {
                        // If everything is below target, return nearest overall.
                        $closestDelta = $bundles
                            ->map(function ($bundle) use ($targetCapacityWatts) {
                                return abs(((float) ($bundle->_parsed_load_watts ?? 0)) - $targetCapacityWatts);
                            })
                            ->min();

                        $bundles = $bundles
                            ->filter(function ($bundle) use ($targetCapacityWatts, $closestDelta) {
                                $delta = abs(((float) ($bundle->_parsed_load_watts ?? 0)) - $targetCapacityWatts);
                                return abs($delta - $closestDelta) < 0.0001;
                            })
                            ->sortBy('_parsed_load_watts')
                            ->values();
                    }
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
                    'is_available' => (bool) ($bundle->is_available ?? true),
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
