<?php

namespace App\Http\Controllers\Api\Website;

use Illuminate\Http\Request;
use App\Models\ProductReview;
use App\Models\ProductReveiews;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductReviewRequest;

class ProductReviewController extends Controller
{
   public function store(StoreProductReviewRequest $request)
{
    try {
        $data = $request->validated();
        $data['user_id'] = auth()->id(); // Get ID from logged-in user

        $review = ProductReveiews::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Review submitted successfully',
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
        $review = ProductReveiews::findOrFail($id);

        // Optional: prevent update if user is not owner
        if ($review->user_id !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $review->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Review updated successfully',
            'data' => $review,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Update failed: ' . $e->getMessage(),
        ], 500);
    }
}
}