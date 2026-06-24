<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductReviewRequest;
use App\Models\Order;
use App\Models\ProductReveiews;
use App\Support\OrderPurchaseItems;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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

    private function normalizeStatus(?string $s): string
    {
        return strtolower(trim((string) ($s ?? '')));
    }

    private function isDeliveredStatus(string $normalized): bool
    {
        return in_array($normalized, ['delivered', 'completed', 'complete'], true);
    }

    public function isReviewAllowedOrder(Order $order): bool
    {
        $status = $this->normalizeStatus($order->order_status);
        if (in_array($status, ['cancelled', 'refunded'], true)) {
            return false;
        }

        return $this->isDeliveredStatus($status);
    }

    private function loadOwnedOrder(int $orderId, int $userId): ?Order
    {
        $order = Order::query()->with(['items.itemable'])->find($orderId);
        if (! $order || (int) $order->user_id !== $userId) {
            return null;
        }

        return $order;
    }

    private function orderAllowsProductReview(int $orderId, int $userId, int $productId): bool
    {
        $order = $this->loadOwnedOrder($orderId, $userId);
        if (! $order || ! $this->isReviewAllowedOrder($order)) {
            return false;
        }

        return OrderPurchaseItems::orderIncludesProduct($order, $productId);
    }

    private function orderAllowsBundleReview(int $orderId, int $userId, int $bundleId): bool
    {
        $order = $this->loadOwnedOrder($orderId, $userId);
        if (! $order || ! $this->isReviewAllowedOrder($order)) {
            return false;
        }

        return OrderPurchaseItems::orderIncludesBundle($order, $bundleId);
    }

    private function hasReviewableOrderForProduct(int $userId, int $productId): bool
    {
        $orders = Order::query()
            ->with(['items.itemable'])
            ->where('user_id', $userId)
            ->get();

        foreach ($orders as $order) {
            if (! $this->isReviewAllowedOrder($order)) {
                continue;
            }
            if (OrderPurchaseItems::orderIncludesProduct($order, $productId)) {
                return true;
            }
        }

        return false;
    }

    private function hasReviewableOrderForBundle(int $userId, int $bundleId): bool
    {
        $orders = Order::query()
            ->with(['items.itemable'])
            ->where('user_id', $userId)
            ->get();

        foreach ($orders as $order) {
            if (! $this->isReviewAllowedOrder($order)) {
                continue;
            }
            if (OrderPurchaseItems::orderIncludesBundle($order, $bundleId)) {
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

        return $this->hasReviewableOrderForProduct($userId, $productId);
    }

    private function userCanReviewBundle(int $userId, int $bundleId, ?int $orderId = null): bool
    {
        if ($orderId !== null && $this->orderAllowsBundleReview($orderId, $userId, $bundleId)) {
            return true;
        }

        return $this->hasReviewableOrderForBundle($userId, $bundleId);
    }

    private function assertBundleReviewsSupported(): ?\Illuminate\Http\JsonResponse
    {
        if (! Schema::hasColumn('product_reveiews', 'bundle_id')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Bundle reviews are not enabled on the server yet. Please run: php artisan migrate',
            ], 503);
        }

        return null;
    }

    private function reviewDeniedMessage(?int $orderId, int $userId, string $itemType): string
    {
        if ($orderId === null) {
            return "You can only review {$itemType}s after your order has been delivered.";
        }

        $order = $this->loadOwnedOrder($orderId, $userId);
        if (! $order) {
            return 'This order was not found on your account.';
        }

        if (! $this->isReviewAllowedOrder($order)) {
            return 'Reviews are available after your order is marked as delivered.';
        }

        return "This {$itemType} was not found on the selected order.";
    }

    private function existingReviewForUser(int $userId, ?int $productId, ?int $bundleId): ?ProductReveiews
    {
        $query = ProductReveiews::query()->where('user_id', $userId);

        if ($bundleId !== null) {
            return $query->where('bundle_id', $bundleId)->first();
        }

        return $query->where('product_id', $productId)->first();
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
                if ($blocked = $this->assertBundleReviewsSupported()) {
                    return $blocked;
                }

                if (! $this->userCanReviewBundle($userId, $bundleId, $orderId)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $this->reviewDeniedMessage($orderId, $userId, 'bundle'),
                    ], 422);
                }

                if ($this->existingReviewForUser($userId, null, $bundleId)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You have already submitted a review for this bundle.',
                    ], 422);
                }

                $review = ProductReveiews::create([
                    'user_id' => $userId,
                    'product_id' => null,
                    'bundle_id' => $bundleId,
                    'order_id' => $orderId,
                    'review' => $data['review'],
                    'rating' => $data['rating'],
                ])->load(['user:id,first_name,sur_name', 'bundle:id,title', 'order:id,order_number']);
            } else {
                if (! $this->userCanReviewProduct($userId, (int) $productId, $orderId)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => $this->reviewDeniedMessage($orderId, $userId, 'product'),
                    ], 422);
                }

                if ($this->existingReviewForUser($userId, $productId, null)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You have already submitted a review for this product.',
                    ], 422);
                }

                $review = ProductReveiews::create([
                    'user_id' => $userId,
                    'product_id' => $productId,
                    'bundle_id' => null,
                    'order_id' => $orderId,
                    'review' => $data['review'],
                    'rating' => $data['rating'],
                ])->load(['user:id,first_name,sur_name', 'product:id,title', 'order:id,order_number']);
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

}
