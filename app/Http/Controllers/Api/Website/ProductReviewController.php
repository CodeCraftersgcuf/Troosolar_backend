<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductReviewRequest;
use App\Models\BundleItems;
use App\Models\LoanApplication;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductReveiews;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = ProductReveiews::query()
                ->with(['user:id,first_name,sur_name', 'product:id,title', 'bundle:id,title', 'order:id,order_number'])
                ->orderByDesc('created_at');

            if ($request->filled('product_id')) {
                $query->where('product_id', (int) $request->input('product_id'));
            }

            if ($request->filled('bundle_id')) {
                $query->where('bundle_id', (int) $request->input('bundle_id'));
            }

            if ($request->filled('order_id')) {
                $query->where('order_id', (int) $request->input('order_id'));
            }

            $mine = filter_var($request->input('mine'), FILTER_VALIDATE_BOOLEAN);
            if ($mine) {
                if (! auth()->check()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unauthorized',
                    ], 401);
                }
                $query->where('user_id', auth()->id());
            }

            $reviews = $query->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Reviews fetched successfully',
                'data' => $reviews,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch reviews: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET ?product_id=&bundle_id=&order_id=
     */
    public function reviewEligibility(Request $request)
    {
        if (! auth()->check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $request->validate([
            'product_id' => 'nullable|integer|exists:products,id|required_without:bundle_id',
            'bundle_id' => 'nullable|integer|exists:bundles,id|required_without:product_id',
            'order_id' => 'nullable|integer|exists:orders,id',
        ]);

        $userId = (int) auth()->id();
        $productId = $request->filled('product_id') ? (int) $request->input('product_id') : null;
        $bundleId = $request->filled('bundle_id') ? (int) $request->input('bundle_id') : null;
        $orderId = $request->filled('order_id') ? (int) $request->input('order_id') : null;

        $eligible = $bundleId !== null
            ? $this->userCanReviewBundle($userId, $bundleId, $orderId)
            : $this->userCanReviewProduct($userId, (int) $productId, $orderId);

        return response()->json([
            'status' => 'success',
            'data' => ['eligible' => $eligible],
        ]);
    }

    private function normalizeOrderStatus(?string $s): string
    {
        return strtolower(trim((string) ($s ?? '')));
    }

    private function isDeliveredStatus(string $normalized): bool
    {
        return in_array($normalized, ['delivered', 'completed', 'complete'], true);
    }

    private function isPaidPaymentStatus(string $normalized): bool
    {
        return in_array($normalized, ['paid', 'confirmed', 'completed', 'success', 'successful'], true);
    }

    /** Customer may review after delivery or once payment is confirmed (shop / buy-now checkout). */
    private function isReviewAllowedOrder(Order $order): bool
    {
        $status = $this->normalizeOrderStatus($order->order_status);
        if (in_array($status, ['cancelled', 'refunded'], true)) {
            return false;
        }
        if ($this->isDeliveredStatus($status)) {
            return true;
        }

        return $this->isPaidPaymentStatus($this->normalizeOrderStatus($order->payment_status));
    }

    private function normalizeLineItemKind(?string $itemableType, ?string $fallbackType = null): ?string
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

    private function orderLineKind(OrderItem $item): ?string
    {
        return $this->normalizeLineItemKind($item->itemable_type);
    }

    private function orderContainsPurchasedProduct(Order $order, int $productId): bool
    {
        return in_array($productId, $this->collectPurchasedProductIds($order), true);
    }

    private function orderContainsPurchasedBundle(Order $order, int $bundleId): bool
    {
        return in_array($bundleId, $this->collectPurchasedBundleIds($order), true);
    }

    /** @return list<int> */
    private function collectPurchasedProductIds(Order $order): array
    {
        $ids = [];

        if ((int) ($order->product_id ?? 0) > 0) {
            $ids[] = (int) $order->product_id;
        }

        $order->loadMissing('items');
        foreach ($order->items as $item) {
            $itemableId = (int) $item->itemable_id;
            if ($itemableId <= 0) {
                continue;
            }

            $kind = $this->orderLineKind($item);
            if ($kind === 'product') {
                $ids[] = $itemableId;
                continue;
            }

            if ($kind === 'bundle') {
                $bundleProductIds = BundleItems::query()
                    ->where('bundle_id', $itemableId)
                    ->pluck('product_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                foreach ($bundleProductIds as $pid) {
                    if ($pid > 0) {
                        $ids[] = $pid;
                    }
                }
            }
        }

        foreach ($this->snapshotLineRows($order) as $row) {
            $kind = $this->snapshotRowKind($row);
            if ($kind !== 'product') {
                continue;
            }
            $id = $this->snapshotRowId($row, 'product');
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /** @return list<int> */
    private function collectPurchasedBundleIds(Order $order): array
    {
        $ids = [];

        if ((int) ($order->bundle_id ?? 0) > 0) {
            $ids[] = (int) $order->bundle_id;
        }

        $order->loadMissing('items');
        foreach ($order->items as $item) {
            $itemableId = (int) $item->itemable_id;
            if ($itemableId <= 0) {
                continue;
            }

            $kind = $this->orderLineKind($item);
            if ($kind === 'bundle' || str_contains(strtolower((string) $item->itemable_type), 'bundle')) {
                $ids[] = $itemableId;
            }
        }

        foreach ($this->snapshotLineRows($order) as $row) {
            $kind = $this->snapshotRowKind($row);
            if ($kind !== 'bundle') {
                continue;
            }
            $id = $this->snapshotRowId($row, 'bundle');
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /** @return list<array<string, mixed>> */
    private function snapshotLineRows(Order $order): array
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

    private function snapshotRowKind(array $row): ?string
    {
        return $this->normalizeLineItemKind(
            $row['itemable_type'] ?? null,
            $row['type'] ?? null
        );
    }

    private function snapshotRowId(array $row, ?string $kind = null): int
    {
        $keys = $kind === 'bundle'
            ? ['itemable_id', 'bundle_id']
            : ($kind === 'product'
                ? ['itemable_id', 'product_id']
                : ['itemable_id', 'bundle_id', 'product_id', 'id']);

        foreach ($keys as $key) {
            $id = (int) ($row[$key] ?? 0);
            if ($id > 0) {
                return $id;
            }
        }

        return 0;
    }

    private function orderAllowsProductReview(int $orderId, int $userId, int $productId): bool
    {
        $order = Order::query()->whereKey($orderId)->where('user_id', $userId)->first();
        if (! $order) {
            return false;
        }

        if (! $this->isReviewAllowedOrder($order)) {
            return false;
        }

        return $this->orderContainsPurchasedProduct($order, $productId);
    }

    private function orderAllowsBundleReview(int $orderId, int $userId, int $bundleId): bool
    {
        $order = Order::query()->whereKey($orderId)->where('user_id', $userId)->first();
        if (! $order) {
            return false;
        }

        if (! $this->isReviewAllowedOrder($order)) {
            return false;
        }

        return $this->orderContainsPurchasedBundle($order, $bundleId);
    }

    private function hasDeliveredOrderForProduct(int $userId, int $productId): bool
    {
        $orders = Order::query()
            ->where('user_id', $userId)
            ->get(['id', 'order_status', 'payment_status', 'product_id', 'bundle_id', 'mono_calculation_id']);

        foreach ($orders as $order) {
            if (! $this->isReviewAllowedOrder($order)) {
                continue;
            }
            if ($this->orderContainsPurchasedProduct($order, $productId)) {
                return true;
            }
        }

        return false;
    }

    private function hasDeliveredOrderForBundle(int $userId, int $bundleId): bool
    {
        $orders = Order::query()
            ->where('user_id', $userId)
            ->get(['id', 'order_status', 'payment_status', 'product_id', 'bundle_id', 'mono_calculation_id']);

        foreach ($orders as $order) {
            if (! $this->isReviewAllowedOrder($order)) {
                continue;
            }
            if ($this->orderContainsPurchasedBundle($order, $bundleId)) {
                return true;
            }
        }

        return false;
    }

    private function userCanReviewProduct(int $userId, int $productId, ?int $orderId = null): bool
    {
        if ($orderId !== null && $this->orderAllowsProductReview($orderId, $userId, $productId)) {
            return true;
        }

        return $this->hasDeliveredOrderForProduct($userId, $productId);
    }

    private function userCanReviewBundle(int $userId, int $bundleId, ?int $orderId = null): bool
    {
        if ($orderId !== null && $this->orderAllowsBundleReview($orderId, $userId, $bundleId)) {
            return true;
        }

        return $this->hasDeliveredOrderForBundle($userId, $bundleId);
    }

    public function store(StoreProductReviewRequest $request)
    {
        try {
            if (! auth()->check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }

            $data = $request->validated();
            $userId = (int) auth()->id();
            $productId = ! empty($data['product_id']) ? (int) $data['product_id'] : null;
            $bundleId = ! empty($data['bundle_id']) ? (int) $data['bundle_id'] : null;
            $orderId = ! empty($data['order_id']) ? (int) $data['order_id'] : null;

            if ($bundleId !== null) {
                if (! $this->userCanReviewBundle($userId, $bundleId, $orderId)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only review bundles from paid or delivered orders.',
                    ], 422);
                }

                $review = ProductReveiews::updateOrCreate(
                    ['user_id' => $userId, 'bundle_id' => $bundleId],
                    [
                        'product_id' => null,
                        'order_id' => $orderId,
                        'review' => $data['review'],
                        'rating' => $data['rating'],
                    ]
                )->load(['user:id,first_name,sur_name', 'bundle:id,title', 'order:id,order_number']);
            } else {
                if (! $this->userCanReviewProduct($userId, (int) $productId, $orderId)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only review products from paid or delivered orders.',
                    ], 422);
                }

                $review = ProductReveiews::updateOrCreate(
                    ['user_id' => $userId, 'product_id' => $productId],
                    [
                        'bundle_id' => null,
                        'order_id' => $orderId,
                        'review' => $data['review'],
                        'rating' => $data['rating'],
                    ]
                )->load(['user:id,first_name,sur_name', 'product:id,title', 'order:id,order_number']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Review saved successfully',
                'data' => $review,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save review: '.$e->getMessage(),
            ], 500);
        }
    }

    public function update(StoreProductReviewRequest $request, $id)
    {
        try {
            if (! auth()->check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }

            $review = ProductReveiews::findOrFail($id);

            if ($review->user_id !== auth()->id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            $data = $request->validated();
            $orderId = ! empty($data['order_id']) ? (int) $data['order_id'] : null;

            if ($review->bundle_id) {
                if (! $this->userCanReviewBundle((int) auth()->id(), (int) $review->bundle_id, $orderId)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only review bundles from paid or delivered orders.',
                    ], 422);
                }
            } elseif (! $this->userCanReviewProduct((int) auth()->id(), (int) $review->product_id, $orderId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only review products from delivered/completed orders.',
                ], 422);
            }

            $review->update([
                'review' => $data['review'],
                'rating' => $data['rating'],
                ...( $orderId !== null ? ['order_id' => $orderId] : [] ),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Review updated successfully',
                'data' => $review->load(['user:id,first_name,sur_name', 'product:id,title', 'bundle:id,title', 'order:id,order_number']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Update failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
