<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Material;
use App\Models\MaterialCategory;
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

            $query = Product::query()
                ->with(['details', 'images', 'reviews.user', 'category']);

            // Keep category narrowing when available, but do not hard-fail when category mapping is off.
            if (!empty($matchedCategoryIds)) {
                $query->whereIn('category_id', $matchedCategoryIds);
            }

            $this->applyPublicAvailabilityFilters($query);

            $products = $this->filterProductsByGroup($query->get(), $normalizedGroup);

            // Self-heal for battery flow:
            // if materials exist but battery products were never seeded, seed missing ones and retry once.
            if ($normalizedGroup === 'battery-only' && $products->isEmpty()) {
                $this->seedBatteryProductsFromMaterials();

                $retryQuery = Product::query()->with(['details', 'images', 'reviews.user', 'category']);
                $this->applyPublicAvailabilityFilters($retryQuery);
                $products = $this->filterProductsByGroup($retryQuery->get(), $normalizedGroup);
            }

            return ResponseHelper::success($products, 'Products fetched successfully.');
        } catch (Exception $e) {
            return ResponseHelper::error('Failed to fetch products for this group.', 500);
        }
    }

    public function getProductsByCategory($categoryId)
    {
        try {
            $category = Category::query()->select(['id', 'title'])->find($categoryId);
            if (!$category) {
                return ResponseHelper::error('Category not found.', 404);
            }

            $query = Product::query()
                ->with(['details', 'images', 'reviews.user', 'category'])
                ->where('category_id', $categoryId);

            $this->applyPublicAvailabilityFilters($query);

            $products = $query->get();

            // Self-heal for categories that were historically uploaded under materials/wrong category.
            // For All-In-One, retag existing matching products and seed from material category "C".
            if ($products->isEmpty() && $this->isAllInOneCategoryTitle((string) $category->title)) {
                $this->syncAllInOneProductsForCategory((int) $categoryId);

                $retryQuery = Product::query()
                    ->with(['details', 'images', 'reviews.user', 'category'])
                    ->where('category_id', $categoryId);
                $this->applyPublicAvailabilityFilters($retryQuery);
                $products = $retryQuery->get();
            }

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

    private function filterProductsByGroup($products, string $normalizedGroup)
    {
        return $products->filter(function ($product) use ($normalizedGroup) {
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
            $isKwhTitle = str_contains($title, 'kwh');
            $isAllInOneSystem = str_contains($title, 'all in one')
                || str_contains($title, 'all-in-one')
                || str_contains($title, 'aio')
                || str_contains($title, 'system');
            $isAllInOneCategory = str_contains($categoryTitle, 'all in one')
                || str_contains($categoryTitle, 'all-in-one')
                || str_contains($categoryTitle, 'aio')
                || str_contains($categoryTitle, 'system');

            return match ($normalizedGroup) {
                'battery-only' => (
                    $isBatteryTitle
                    || $isBatteryCategory
                    || $isKwhTitle
                )
                    && !$isPanelTitle
                    && !$isPanelCategory
                    && !$isAllInOneSystem
                    && !$isAllInOneCategory
                    && !($isInverterTitle && !$isKwhTitle),
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
    }

    private function seedBatteryProductsFromMaterials(): void
    {
        $batteryProductCategory = Category::firstOrCreate(
            ['title' => 'Lithium Batteries'],
            ['icon' => null]
        );

        $defaultBrand = Brand::first();
        if (!$defaultBrand) {
            $defaultBrand = Brand::create([
                'title' => 'Default Brand',
                'category_id' => $batteryProductCategory->id,
                'icon' => null,
            ]);
        }

        $batteryMaterialCategoryIds = MaterialCategory::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereIn('code', ['E', 'K'])
                    ->orWhereRaw('LOWER(name) like ?', ['%battery%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%lithium%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%rack%']);
            })
            ->pluck('id')
            ->all();

        if (empty($batteryMaterialCategoryIds)) {
            return;
        }

        $materials = Material::query()
            ->whereIn('material_category_id', $batteryMaterialCategoryIds)
            ->where('is_active', true)
            ->get();

        foreach ($materials as $material) {
            Product::updateOrCreate(
                ['title' => $material->name],
                [
                    'category_id' => $batteryProductCategory->id,
                    'brand_id' => $defaultBrand->id,
                    'price' => 1000.00,
                    'discount_price' => 1000.00,
                    'stock' => 'In Stock',
                    'installation_price' => 0.00,
                    'top_deal' => false,
                    'installation_compulsory' => false,
                    'is_available' => true,
                    'featured_image' => 'https://troosolar.hmstech.org/storage/products/e212b55b-057a-4a39-8d80-d241169cdac0.png',
                ]
            );
        }
    }

    private function isAllInOneCategoryTitle(string $title): bool
    {
        $name = strtolower(trim($title));
        return str_contains($name, 'all in one')
            || str_contains($name, 'all-in-one')
            || str_contains($name, 'aio')
            || str_contains($name, 'system');
    }

    private function isAllInOneProductTitle(string $title): bool
    {
        $name = strtolower(trim($title));
        if ($name === '') {
            return false;
        }

        return str_contains($name, 'all in one')
            || str_contains($name, 'all-in-one')
            || str_contains($name, 'aio')
            || (str_contains($name, 'kva') && str_contains($name, 'kwh'));
    }

    private function syncAllInOneProductsForCategory(int $categoryId): void
    {
        // 1) Retag existing products that clearly match all-in-one naming.
        Product::query()
            ->select(['id', 'title', 'category_id'])
            ->get()
            ->filter(function ($product) {
                return $this->isAllInOneProductTitle((string) ($product->title ?? ''));
            })
            ->each(function ($product) use ($categoryId) {
                if ((int) $product->category_id !== $categoryId) {
                    $product->category_id = $categoryId;
                    $product->save();
                }
            });

        // 2) If products were uploaded only as materials, seed missing product rows from material category C.
        $allInOneMaterialCategoryIds = MaterialCategory::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('code', 'C')
                    ->orWhereRaw('LOWER(name) like ?', ['%all in one%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%all-in-one%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%aio%'])
                    ->orWhereRaw('LOWER(name) like ?', ['%system%']);
            })
            ->pluck('id')
            ->all();

        if (empty($allInOneMaterialCategoryIds)) {
            return;
        }

        $defaultBrand = Brand::query()->where('category_id', $categoryId)->first();
        if (!$defaultBrand) {
            $defaultBrand = Brand::first();
        }
        if (!$defaultBrand) {
            return;
        }

        $materials = Material::query()
            ->whereIn('material_category_id', $allInOneMaterialCategoryIds)
            ->where('is_active', true)
            ->get();

        foreach ($materials as $material) {
            if (!$this->isAllInOneProductTitle((string) ($material->name ?? ''))) {
                continue;
            }

            Product::updateOrCreate(
                ['title' => $material->name],
                [
                    'category_id' => $categoryId,
                    'brand_id' => $defaultBrand->id,
                    'price' => 1000.00,
                    'discount_price' => 1000.00,
                    'stock' => 'In Stock',
                    'installation_price' => 0.00,
                    'top_deal' => false,
                    'installation_compulsory' => false,
                    'is_available' => true,
                    'featured_image' => 'https://troosolar.hmstech.org/storage/products/e212b55b-057a-4a39-8d80-d241169cdac0.png',
                ]
            );
        }
    }
}

