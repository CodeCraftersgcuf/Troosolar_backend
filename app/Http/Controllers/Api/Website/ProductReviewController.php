<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductReviewRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
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
                if (!auth()->check()) {
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
                'message' => 'Failed to fetch reviews: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Order items store polymorphic type as the model class (e.g. App\Models\Product), not the string "product".
     * BNPL snapshots may use the short name "product". Legacy rows may only set orders.product_id.
     */
    private function hasDeliveredOrderForProduct(int $userId, int $productId): bool
    {
        $statuses = ['delivered', 'completed'];

        $productMorphTypes = array_values(array_unique([
            Product::class,
            'product',
            class_basename(Product::class),
        ]));

        $viaLineItems = OrderItem::query()
            ->where('itemable_id', $productId)
            ->whereIn('itemable_type', $productMorphTypes)
            ->whereHas('order', function ($q) use ($userId, $statuses) {
                $q->where('user_id', $userId)
                    ->whereIn('order_status', $statuses);
            })
            ->exists();

        if ($viaLineItems) {
            return true;
        }

        return Order::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->whereIn('order_status', $statuses)
            ->exists();
    }

    public function store(StoreProductReviewRequest $request)
    {
    try {
        if (!auth()->check()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }

        $data = $request->validated();
        $userId = (int) auth()->id();
        $productId = (int) $data['product_id'];

        if (!$this->hasDeliveredOrderForProduct($userId, $productId)) {
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
            'message' => 'Failed to save review: ' . $e->getMessage(),
        ], 500);
    }
    }

    public function update(StoreProductReviewRequest $request, $id)
    {
    try {
        if (!auth()->check()) {
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
        if (!$this->hasDeliveredOrderForProduct((int) auth()->id(), (int) $review->product_id)) {
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
            'message' => 'Update failed: ' . $e->getMessage(),
        ], 500);
    }
    }
}