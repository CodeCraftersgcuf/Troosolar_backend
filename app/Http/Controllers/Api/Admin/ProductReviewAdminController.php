<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReveiews;
use Illuminate\Http\Request;

class ProductReviewAdminController extends Controller
{
    private function ensureAdmin()
    {
        $role = strtolower((string) (auth()->user()->role ?? ''));
        if ($role !== 'admin') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }
        return null;
    }

    public function index(Request $request)
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        $query = ProductReveiews::query()
            ->with(['user:id,first_name,sur_name', 'product:id,title'])
            ->orderByDesc('created_at');

        if ($request->filled('product_id')) {
            $query->where('product_id', (int) $request->input('product_id'));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Admin reviews fetched successfully',
            'data' => $query->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        $validated = $request->validate([
            'review' => 'required|string',
            'rating' => 'required|in:1,2,3,4,5',
        ]);

        $review = ProductReveiews::findOrFail($id);
        $review->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Review updated successfully',
            'data' => $review->load(['user:id,first_name,sur_name', 'product:id,title']),
        ]);
    }

    public function destroy($id)
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        $review = ProductReveiews::findOrFail($id);
        $review->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Review deleted successfully',
        ]);
    }
}

