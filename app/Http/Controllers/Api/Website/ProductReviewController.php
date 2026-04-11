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
                ->with(['user:id,first_name,sur_name'])
                ->orderByDesc('created_at');

            if ($request->filled('product_id')) {
                $query->where('product_id', (int) $request->input('product_id'));
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
     * GET ?product_id=&order_id= — whether the current user may review this product (optionally scoped to one order).
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
            'product_id' => 'required|integer|exists:products,id',
            'order_id' => 'nullable|integer|exists:orders,id',
        ]);

        $userId = (int) auth()->id();
        $productId = (int) $request->input('product_id');
        $orderId = $request->filled('order_id') ? (int) $request->input('order_id') : null;

        $eligible = $this->userCanReviewProduct($userId, $productId, $orderId);

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
        return in_array($normalized, ['delivered', 'completed'], true);
    }

    /**
     * True if this order line is a direct product match or a bundle that includes the product.
     */
    private function orderLineContainsProduct(OrderItem $item, int $productId): bool
    {
        $type = (string) $item->itemable_type;
        $basename = class_basename($type);
        $itemableId = (int) $item->itemable_id;

        if ($itemableId <= 0) {
            return false;
        }

        if (strcasecmp($basename, 'Product') === 0) {
            return $itemableId === $productId;
        }

        if (strcasecmp($basename, 'Bundles') === 0) {
            return BundleItems::query()
                ->where('bundle_id', $itemableId)
                ->where('product_id', $productId)
                ->exists();
        }

        return false;
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

    private function userCanReviewProduct(int $userId, int $productId, ?int $orderId = null): bool
    {
        if ($orderId !== null && $this->orderAllowsProductReview($orderId, $userId, $productId)) {
            return true;
        }

        return $this->hasDeliveredOrderForProduct($userId, $productId);
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
            $productId = (int) $data['product_id'];
            $orderId = ! empty($data['order_id']) ? (int) $data['order_id'] : null;

            if (! $this->userCanReviewProduct($userId, $productId, $orderId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only review products from delivered/completed orders.',
                ], 422);
            }

            $review = ProductReveiews::updateOrCreate(
                ['user_id' => $userId, 'product_id' => $productId],
                [
                    'review' => $data['review'],
                    'rating' => $data['rating'],
                ]
            )->load('user:id,first_name,sur_name');

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

            if (! $this->userCanReviewProduct((int) auth()->id(), (int) $review->product_id, $orderId)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only review products from delivered/completed orders.',
                ], 422);
            }

            $review->update([
                'review' => $data['review'],
                'rating' => $data['rating'],
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Review updated successfully',
                'data' => $review->load('user:id,first_name,sur_name'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Update failed: '.$e->getMessage(),
            ], 500);
        }
    }
}
