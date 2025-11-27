<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Models\AuditRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Exception;

class AuditAdminController extends Controller
{
    /**
     * Get all audit requests
     * GET /api/admin/audit/requests
     */
    public function index(Request $request)
    {
        try {
            $query = AuditRequest::with(['user:id,first_name,sur_name,email,phone', 'order:id,order_number,total_price,payment_status', 'approver:id,first_name,sur_name,email']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by audit type
            if ($request->has('audit_type')) {
                $query->where('audit_type', $request->audit_type);
            }

            // Search by user name, email, or property address
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('first_name', 'like', "%{$search}%")
                                  ->orWhere('sur_name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhere('property_address', 'like', "%{$search}%")
                    ->orWhere('property_state', 'like', "%{$search}%");
                });
            }

            $auditRequests = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return ResponseHelper::success($auditRequests, 'Audit requests retrieved successfully');
        } catch (Exception $e) {
            Log::error('Audit Admin Index Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to retrieve audit requests', 500);
        }
    }

    /**
     * Get single audit request details
     * GET /api/admin/audit/requests/{id}
     */
    public function show($id)
    {
        try {
            $auditRequest = AuditRequest::with([
                'user:id,first_name,sur_name,email,phone',
                'order:id,order_number,total_price,payment_status,order_status',
                'approver:id,first_name,sur_name,email'
            ])->findOrFail($id);

            return ResponseHelper::success([
                'id' => $auditRequest->id,
                'user' => [
                    'id' => $auditRequest->user->id,
                    'name' => $auditRequest->user->first_name . ' ' . $auditRequest->user->sur_name,
                    'email' => $auditRequest->user->email,
                    'phone' => $auditRequest->user->phone,
                ],
                'audit_type' => $auditRequest->audit_type,
                'customer_type' => $auditRequest->customer_type,
                'property_state' => $auditRequest->property_state,
                'property_address' => $auditRequest->property_address,
                'property_landmark' => $auditRequest->property_landmark,
                'property_floors' => $auditRequest->property_floors,
                'property_rooms' => $auditRequest->property_rooms,
                'is_gated_estate' => $auditRequest->is_gated_estate,
                'estate_name' => $auditRequest->estate_name,
                'estate_address' => $auditRequest->estate_address,
                'status' => $auditRequest->status,
                'admin_notes' => $auditRequest->admin_notes,
                'approved_by' => $auditRequest->approver ? [
                    'id' => $auditRequest->approver->id,
                    'name' => $auditRequest->approver->first_name . ' ' . $auditRequest->approver->sur_name,
                    'email' => $auditRequest->approver->email,
                ] : null,
                'approved_at' => $auditRequest->approved_at?->toIso8601String(),
                'order' => $auditRequest->order ? [
                    'id' => $auditRequest->order->id,
                    'order_number' => $auditRequest->order->order_number,
                    'total_price' => $auditRequest->order->total_price,
                    'payment_status' => $auditRequest->order->payment_status,
                    'order_status' => $auditRequest->order->order_status,
                ] : null,
                'created_at' => $auditRequest->created_at->toIso8601String(),
                'updated_at' => $auditRequest->updated_at->toIso8601String(),
            ], 'Audit request retrieved successfully');
        } catch (Exception $e) {
            Log::error('Audit Admin Show Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to retrieve audit request', 500);
        }
    }

    /**
     * Approve or reject audit request
     * PUT /api/admin/audit/requests/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'status' => 'required|in:approved,rejected,completed',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            $auditRequest = AuditRequest::findOrFail($id);

            $auditRequest->status = $data['status'];
            $auditRequest->admin_notes = $data['admin_notes'] ?? $auditRequest->admin_notes;
            
            if ($data['status'] === 'approved' || $data['status'] === 'completed') {
                $auditRequest->approved_by = Auth::id();
                $auditRequest->approved_at = now();
            }

            $auditRequest->save();

            return ResponseHelper::success([
                'id' => $auditRequest->id,
                'status' => $auditRequest->status,
                'admin_notes' => $auditRequest->admin_notes,
                'approved_by' => $auditRequest->approver ? [
                    'id' => $auditRequest->approver->id,
                    'name' => $auditRequest->approver->first_name . ' ' . $auditRequest->approver->sur_name,
                ] : null,
                'approved_at' => $auditRequest->approved_at?->toIso8601String(),
            ], 'Audit request status updated successfully');
        } catch (Exception $e) {
            Log::error('Audit Admin Update Status Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to update audit request status', 500);
        }
    }
}
