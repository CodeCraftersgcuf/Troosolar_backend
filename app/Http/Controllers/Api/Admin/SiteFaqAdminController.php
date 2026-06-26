<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\SiteFaq;
use Illuminate\Http\Request;

class SiteFaqAdminController extends Controller
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

    public function index()
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        $faqs = SiteFaq::query()->ordered()->get();

        return ResponseHelper::success($faqs, 'FAQs retrieved successfully');
    }

    /**
     * POST /admin/site/faqs/reorder
     * Body: { "orders": [ { "id": 1, "sort_order": 1 }, ... ] }
     */
    public function reorder(Request $request)
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        $entries = $request->input('orders', []);
        if (! is_array($entries) || $entries === []) {
            return ResponseHelper::error('Invalid payload.', 422);
        }

        foreach ($entries as $entry) {
            $id = $entry['id'] ?? null;
            $order = $entry['sort_order'] ?? null;
            if ($id === null || $order === null) {
                continue;
            }
            SiteFaq::where('id', (int) $id)->update(['sort_order' => (int) $order]);
        }

        return ResponseHelper::success(null, 'FAQ order updated successfully');
    }

    public function store(Request $request)
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        $validated = $request->validate([
            'question' => 'required|string|max:500',
            'answer' => 'required|string|max:10000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $requestedOrder = isset($validated['sort_order']) ? (int) $validated['sort_order'] : 0;
        $nextOrder = $requestedOrder > 0
            ? $requestedOrder
            : ((int) SiteFaq::max('sort_order')) + 1;

        $faq = SiteFaq::create([
            'question' => trim($validated['question']),
            'answer' => trim($validated['answer']),
            'sort_order' => $nextOrder,
            'is_active' => array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : true,
        ]);

        return ResponseHelper::success($faq, 'FAQ created successfully', 201);
    }

    public function update(Request $request, $id)
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        $validated = $request->validate([
            'question' => 'sometimes|required|string|max:500',
            'answer' => 'sometimes|required|string|max:10000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $faq = SiteFaq::findOrFail($id);

        if (array_key_exists('question', $validated)) {
            $faq->question = trim($validated['question']);
        }
        if (array_key_exists('answer', $validated)) {
            $faq->answer = trim($validated['answer']);
        }
        if (array_key_exists('sort_order', $validated)) {
            $faq->sort_order = (int) $validated['sort_order'];
        }
        if (array_key_exists('is_active', $validated)) {
            $faq->is_active = (bool) $validated['is_active'];
        }

        $faq->save();

        return ResponseHelper::success($faq, 'FAQ updated successfully');
    }

    public function destroy($id)
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        SiteFaq::findOrFail($id)->delete();

        return ResponseHelper::success(null, 'FAQ deleted successfully');
    }
}
