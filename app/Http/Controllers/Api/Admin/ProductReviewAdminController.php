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
        if (! in_array($role, ['admin', 'superadmin', 'super_admin'], true)) {
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

    /**
     * PUT /api/admin/product-reviews/{id}/reply
     * Store or update the public admin response shown under the customer's review.
     */
    public function reply(Request $request, $id)
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        $validated = $request->validate([
            'admin_reply' => 'nullable|string|max:10000',
        ]);

        $review = ProductReveiews::findOrFail($id);
        $text = isset($validated['admin_reply']) ? trim((string) $validated['admin_reply']) : '';
        $review->admin_reply = $text !== '' ? $text : null;
        $review->admin_replied_at = $review->admin_reply ? now() : null;
        $review->save();

        return response()->json([
            'status' => 'success',
            'message' => $review->admin_reply ? 'Reply saved successfully' : 'Reply removed',
            'data' => $review->load(['user:id,first_name,sur_name', 'product:id,title']),
        ]);
    }
}

