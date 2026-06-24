<?php

namespace App\Support;

use App\Models\Material;
use App\Models\Product;

class ProductMaterialPricing
{
    public static function priceFromMaterial(Material $material, ?Product $existing = null): float
    {
        $rawRate = (float) ($material->selling_rate ?? $material->rate ?? 0);
        if ($rawRate > 0) {
            return round($rawRate, 2);
        }

        if ($existing) {
            $existingPrice = (float) ($existing->discount_price ?? $existing->price ?? 0);
            if ($existingPrice > 0) {
                return round($existingPrice, 2);
            }
        }

        return 1000.00;
    }

    public static function productUnitPrice(Product $product): float
    {
        $discount = (float) ($product->discount_price ?? 0);
        $base = (float) ($product->price ?? 0);

        return $discount > 0 ? round($discount, 2) : round(max(0, $base), 2);
    }

    /**
     * Align product catalog prices with matching active materials (by title).
     *
     * @param  iterable<int, Product>  $products
     */
    public static function syncPricesFromMaterials(iterable $products): void
    {
        foreach ($products as $product) {
            $title = trim((string) ($product->title ?? ''));
            if ($title === '') {
                continue;
            }

            $material = Material::query()
                ->where('name', $title)
                ->where('is_active', true)
                ->first();

            if (! $material) {
                continue;
            }

            $price = self::priceFromMaterial($material, $product);
            $current = self::productUnitPrice($product);
            if (abs($current - $price) < 0.01) {
                continue;
            }

            $product->update([
                'price' => $price,
                'discount_price' => $price,
            ]);
        }
    }
}
