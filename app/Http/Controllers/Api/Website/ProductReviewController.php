<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductReviewRequest;
use App\Models\BundleItems;
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

    private function orderLineContainsProduct(OrderItem $item, int $productId): bool
    {
        $itemableId = (int) $item->itemable_id;
        if ($itemableId <= 0) {
            return false;
        }

        $kind = $this->orderLineKind($item);
        if ($kind === 'product') {
            return $itemableId === $productId;
        }

        if ($kind === 'bundle') {
            return BundleItems::query()
                ->where('bundle_id', $itemableId)
                ->where('product_id', $productId)
                ->exists();
        }

        return false;
    }

    private function orderLineContainsBundle(OrderItem $item, int $bundleId): bool
    {
        $itemableId = (int) $item->itemable_id;

        return $this->orderLineKind($item) === 'bundle' && $itemableId === $bundleId;
    }

    private function orderContainsPurchasedProduct(Order $order, int $productId): bool
    {
        if ((int) ($order->product_id ?? 0) === $productId) {
            return true;
        }

        $items = OrderItem::query()
            ->where('order_id', $order->id)
            ->get(['itemable_type', 'itemable_id']);

        foreach ($items as $item) {
            if ($this->orderLineContainsProduct($item, $productId)) {
                return true;
            }
        }

        return false;
    }

    private function orderContainsPurchasedBundle(Order $order, int $bundleId): bool
    {
        if ((int) ($order->bundle_id ?? 0) === $bundleId) {
            return true;
        }

        $items = OrderItem::query()
            ->where('order_id', $order->id)
            ->get(['itemable_type', 'itemable_id']);

        foreach ($items as $item) {
            if ($this->orderLineContainsBundle($item, $bundleId)) {
                return true;
            }
        }

        return false;
    }

    private function orderAllowsProductReview(int $orderId, int $userId, int $productId): bool
    {
        $order = Order::query()->whereKey($orderId)->where('user_id', $userId)->first();
        if (! $order) {
            return false;
        }

        if (! $this->isDeliveredStatus($this->normalizeOrderStatus($order->order_status))) {
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

        if (! $this->isDeliveredStatus($this->normalizeOrderStatus($order->order_status))) {
            return false;
        }

        return $this->orderContainsPurchasedBundle($order, $bundleId);
    }

    private function hasDeliveredOrderForProduct(int $userId, int $productId): bool
    {
        $orders = Order::query()
            ->where('user_id', $userId)
            ->get(['id', 'order_status', 'product_id']);

        foreach ($orders as $order) {
            if (! $this->isDeliveredStatus($this->normalizeOrderStatus($order->order_status))) {
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
            ->get(['id', 'order_status', 'bundle_id']);

        foreach ($orders as $order) {
            if (! $this->isDeliveredStatus($this->normalizeOrderStatus($order->order_status))) {
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
                        'message' => 'You can only review bundles from delivered/completed orders.',
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
                        'message' => 'You can only review products from delivered/completed orders.',
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
                        'message' => 'You can only review bundles from delivered/completed orders.',
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
