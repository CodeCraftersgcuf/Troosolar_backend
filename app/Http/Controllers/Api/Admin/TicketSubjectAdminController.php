<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\TicketSubject;
use Illuminate\Http\Request;

class TicketSubjectAdminController extends Controller
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

        $subjects = TicketSubject::query()->ordered()->get();

        return ResponseHelper::success($subjects, 'Ticket subjects retrieved successfully');
    }

    public function store(Request $request)
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $subject = TicketSubject::create([
            'title' => trim($validated['title']),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_active' => array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : true,
        ]);

        return ResponseHelper::success($subject, 'Ticket subject created successfully', 201);
    }

    public function update(Request $request, $id)
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $subject = TicketSubject::findOrFail($id);

        if (array_key_exists('title', $validated)) {
            $subject->title = trim($validated['title']);
        }
        if (array_key_exists('sort_order', $validated)) {
            $subject->sort_order = (int) $validated['sort_order'];
        }
        if (array_key_exists('is_active', $validated)) {
            $subject->is_active = (bool) $validated['is_active'];
        }

        $subject->save();

        return ResponseHelper::success($subject, 'Ticket subject updated successfully');
    }

    public function destroy($id)
    {
        if ($unauthorized = $this->ensureAdmin()) {
            return $unauthorized;
        }

        TicketSubject::findOrFail($id)->delete();

        return ResponseHelper::success(null, 'Ticket subject deleted successfully');
    }
}
