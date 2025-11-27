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
     * GET /api/bundles
     * Get all active bundles (Public endpoint for Buy Now flow)
     */
    public function index(Request $request)
    {
        try {
            // Get all bundles
            $bundles = Bundles::with(['bundleItems.product', 'customServices'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($bundle) {
                    return [
                        'id' => $bundle->id,
                        'title' => $bundle->title,
                        'bundle_type' => $bundle->bundle_type ?? null,
                        'featured_image' => $bundle->featured_image_url ?? $bundle->featured_image ?? null,
                        'total_price' => (float) ($bundle->total_price ?? 0),
                        'discount_price' => $bundle->discount_price ? (float) $bundle->discount_price : null,
                        'description' => $bundle->description ?? null,
                        'is_active' => true,
                        'created_at' => $bundle->created_at?->toIso8601String(),
                        'updated_at' => $bundle->updated_at?->toIso8601String(),
                    ];
                });

            // Return in format that frontend expects (handle multiple formats)
            return ResponseHelper::success($bundles, 'Bundles retrieved successfully');
        } catch (Exception $e) {
            Log::error('Error fetching bundles: ' . $e->getMessage());
            return ResponseHelper::error('Failed to fetch bundles', 500);
        }
    }
}
