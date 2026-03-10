<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductSelectionController extends Controller
{
    public function getProductsByGroup(string $groupType)
    {
        try {
            $normalizedGroup = strtolower(trim($groupType));

            $categories = Category::query()
                ->select(['id', 'title'])
                ->get();

            $matchedCategoryIds = $categories
                ->filter(function ($category) use ($normalizedGroup) {
                    $name = strtolower(trim((string) ($category->title ?? '')));

                    $isBatteryCategory = str_contains($name, 'battery')
                        || str_contains($name, 'batteries')
                        || str_contains($name, 'lithium')
                        || str_contains($name, 'battries');

                    $isInverterCategory = str_contains($name, 'inverter');
                    $isPanelCategory = str_contains($name, 'panel') || str_contains($name, 'pv');

                    return match ($normalizedGroup) {
                        'battery-only' => $isBatteryCategory,
                        'inverter-only' => $isInverterCategory,
                        'panels-only' => $isPanelCategory,
                        'inverter-battery' => $isInverterCategory || $isBatteryCategory,
                        'full-kit' => $isInverterCategory || $isBatteryCategory || $isPanelCategory,
                        default => false,
                    };
                })
                ->pluck('id')
                ->values()
                ->all();

            if (empty($matchedCategoryIds)) {
                return ResponseHelper::success([], 'No matching categories found for this product group.');
            }

            $query = Product::query()
                ->with(['details', 'images', 'reviews', 'category'])
                ->whereIn('category_id', $matchedCategoryIds);

            $this->applyPublicAvailabilityFilters($query);

            $products = $query->get();

            // Final strict pass by product title so "panels-only" never leaks inverter/battery items
            $products = $products->filter(function ($product) use ($normalizedGroup) {
                $title = strtolower(trim((string) ($product->title ?? '')));
                $categoryTitle = strtolower(trim((string) ($product->category->title ?? '')));
                $isBatteryTitle = str_contains($title, 'battery')
                    || str_contains($title, 'batteries')
                    || str_contains($title, 'lithium')
                    || str_contains($title, 'rack');
                $isBatteryCategory = str_contains($categoryTitle, 'battery')
                    || str_contains($categoryTitle, 'batteries')
                    || str_contains($categoryTitle, 'lithium')
                    || str_contains($categoryTitle, 'rack');
                $isInverterTitle = str_contains($title, 'inverter');
                $isInverterCategory = str_contains($categoryTitle, 'inverter');
                $isPanelTitle = str_contains($title, 'panel') || str_contains($title, 'pv');
                $isPanelCategory = str_contains($categoryTitle, 'panel') || str_contains($categoryTitle, 'pv');
                $isAllInOneSystem = str_contains($title, 'all in one')
                    || str_contains($title, 'all-in-one')
                    || str_contains($title, 'aio')
                    || str_contains($title, 'system');
                $isAllInOneCategory = str_contains($categoryTitle, 'all in one')
                    || str_contains($categoryTitle, 'all-in-one')
                    || str_contains($categoryTitle, 'aio')
                    || str_contains($categoryTitle, 'system');

                return match ($normalizedGroup) {
                    // Keep battery-only to standalone battery products only
                    'battery-only' => ($isBatteryTitle || $isBatteryCategory)
                        && !$isInverterTitle
                        && !$isPanelTitle
                        && !$isInverterCategory
                        && !$isPanelCategory
                        && !$isAllInOneSystem
                        && !$isAllInOneCategory,
                    'inverter-only' => ($isInverterTitle || $isInverterCategory)
                        && !$isBatteryTitle
                        && !$isPanelTitle
                        && !$isBatteryCategory
                        && !$isPanelCategory,
                    'panels-only' => ($isPanelTitle || $isPanelCategory)
                        && !$isInverterTitle
                        && !$isBatteryTitle
                        && !$isInverterCategory
                        && !$isBatteryCategory,
                    default => true,
                };
            })->values();

            return ResponseHelper::success($products, 'Products fetched successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch products for this group.', 500);
        }
    }

    public function getProductsByCategory($categoryId)
    {
        try {
            $query = Product::query()
                ->with(['details', 'images', 'reviews', 'category'])
                ->where('category_id', $categoryId);

            $this->applyPublicAvailabilityFilters($query);

            $products = $query->get();

            return ResponseHelper::success($products, 'Products fetched by category.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch products by category.', 500);
        }
    }

    private function applyPublicAvailabilityFilters(Builder $query): void
    {
        if (Schema::hasColumn('products', 'is_available')) {
            $query->where('is_available', true);
        }

        $normalizedStockExpr = DB::raw("LOWER(REPLACE(REPLACE(REPLACE(TRIM(stock), ' ', ''), '-', ''), '_', ''))");
        $query->where(function (Builder $q) use ($normalizedStockExpr) {
            $q->whereRaw('CAST(stock AS DECIMAL(10,2)) > 0')
                ->orWhereIn($normalizedStockExpr, ['instock', 'available', 'true', 'yes']);
        });
    }
}

