<?php

namespace App\Support;

use App\Models\Bundles;
use App\Models\LoanApplication;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;

/**
 * Resolve product/bundle IDs purchased on an order (matches order detail API lines).
 */
class OrderPurchaseItems
{
    public static function normalizeKind(?string $itemableType, ?string $fallbackType = null): ?string
    {
        $raw = strtolower(trim((string) ($itemableType ?? $fallbackType ?? '')));
        if ($raw === '') {
            return null;
        }
        if ($raw === 'product' || str_ends_with($raw, '\\product') || $raw === 'products') {
            return 'product';
        }
        if ($raw === 'bundle' || $raw === 'bundles' || str_contains($raw, 'bundle')) {
            return 'bundle';
        }

        return null;
    }

    /** @return list<int> */
    public static function bundleIds(Order $order): array
    {
        $ids = [];

        if ((int) ($order->bundle_id ?? 0) > 0) {
            $ids[] = (int) $order->bundle_id;
        }

        $order->loadMissing(['items.itemable']);

        foreach ($order->items as $item) {
            self::appendBundleIdFromLine($ids, $item);
        }

        foreach (self::snapshotRows($order) as $row) {
            if (self::normalizeKind($row['itemable_type'] ?? null, $row['type'] ?? null) !== 'bundle') {
                continue;
            }
            $id = self::snapshotId($row, 'bundle');
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /** @return list<int> */
    public static function productIds(Order $order): array
    {
        $ids = [];

        if ((int) ($order->product_id ?? 0) > 0) {
            $ids[] = (int) $order->product_id;
        }

        $order->loadMissing(['items.itemable']);

        foreach ($order->items as $item) {
            $itemableId = (int) $item->itemable_id;
            if ($itemableId <= 0) {
                continue;
            }

            if ($item->itemable instanceof Product) {
                $ids[] = (int) $item->itemable->id;
                continue;
            }

            if (self::normalizeKind($item->itemable_type) === 'product') {
                $ids[] = $itemableId;
            }
        }

        foreach (self::snapshotRows($order) as $row) {
            if (self::normalizeKind($row['itemable_type'] ?? null, $row['type'] ?? null) !== 'product') {
                continue;
            }
            $id = self::snapshotId($row, 'product');
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    public static function orderIncludesBundle(Order $order, int $bundleId): bool
    {
        if ($bundleId <= 0) {
            return false;
        }

        return in_array($bundleId, self::bundleIds($order), true);
    }

    public static function orderIncludesProduct(Order $order, int $productId): bool
    {
        if ($productId <= 0) {
            return false;
        }

        return in_array($productId, self::productIds($order), true);
    }

    /** @param list<int> $ids */
    private static function appendBundleIdFromLine(array &$ids, OrderItem $item): void
    {
        $itemableId = (int) $item->itemable_id;
        if ($itemableId <= 0) {
            return;
        }

        if ($item->itemable instanceof Bundles) {
            $ids[] = (int) $item->itemable->id;

            return;
        }

        $kind = self::normalizeKind($item->itemable_type);
        if ($kind === 'bundle' || str_contains(strtolower((string) $item->itemable_type), 'bundle')) {
            $ids[] = $itemableId;

            return;
        }

        // Legacy rows: itemable_id points at bundles.id even when morph type string is odd
        if (Bundles::query()->whereKey($itemableId)->exists()
            && $kind !== 'product'
            && ! ($item->itemable instanceof Product)) {
            $ids[] = $itemableId;

            return;
        }

        // Last resort: line id matches a bundle and is not an explicit product line
        if (Bundles::query()->whereKey($itemableId)->exists()
            && self::normalizeKind($item->itemable_type) !== 'product') {
            $ids[] = $itemableId;
        }
    }

    /** @return list<array<string, mixed>> */
    private static function snapshotRows(Order $order): array
    {
        if (! $order->mono_calculation_id) {
            return [];
        }

        $application = LoanApplication::query()
            ->where('mono_loan_calculation', $order->mono_calculation_id)
            ->where('user_id', $order->user_id)
            ->first();

        if (! $application || ! is_array($application->order_items_snapshot)) {
            return [];
        }

        return $application->order_items_snapshot;
    }

    private static function snapshotId(array $row, string $kind): int
    {
        $keys = $kind === 'bundle'
            ? ['itemable_id', 'bundle_id']
            : ['itemable_id', 'product_id'];

        foreach ($keys as $key) {
            $id = (int) ($row[$key] ?? 0);
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }
}
