<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Models\AuditRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuditController extends Controller
{
    /**
     * Submit an audit request with property details
     * POST /api/audit/request
     */
    public function submit(Request $request)
    {
        try {
            // Validate required fields - property details required for home-office audits
            $data = $request->validate([
                'audit_type' => 'required|in:home-office,commercial',
                'customer_type' => 'nullable|in:residential,sme,commercial',
                'property_state' => 'required_if:audit_type,home-office|nullable|string|max:255',
                'property_address' => 'required_if:audit_type,home-office|nullable|string',
                'property_landmark' => 'nullable|string|max:255',
                'property_floors' => 'nullable|integer|min:0',
                'property_rooms' => 'nullable|integer|min:0',
                'is_gated_estate' => 'nullable|boolean',
                'estate_name' => 'nullable|required_if:is_gated_estate,true|string|max:255',
                'estate_address' => 'nullable|required_if:is_gated_estate,true|string',
            ]);

            // Ensure user is authenticated
            $userId = Auth::id();
            if (!$userId) {
                return ResponseHelper::error('User not authenticated', 401);
            }

            // Set status to pending for all audit types initially
            $status = 'pending';

            // Prepare data for creation
            $auditData = [
                'user_id' => $userId,
                'audit_type' => $data['audit_type'],
                'customer_type' => $data['customer_type'] ?? null,
                'property_state' => $data['property_state'] ?? null,
                'property_address' => $data['property_address'] ?? null,
                'property_landmark' => $data['property_landmark'] ?? null,
                'property_floors' => $data['property_floors'] ?? null,
                'property_rooms' => $data['property_rooms'] ?? null,
                'is_gated_estate' => $data['is_gated_estate'] ?? false,
                'estate_name' => $data['estate_name'] ?? null,
                'estate_address' => $data['estate_address'] ?? null,
                'status' => $status,
            ];

            // Create audit request
            $auditRequest = AuditRequest::create($auditData);

            if (!$auditRequest) {
                Log::error('Audit request creation returned null', ['data' => $auditData]);
                return ResponseHelper::error('Failed to create audit request', 500);
            }

            return ResponseHelper::success([
                'id' => $auditRequest->id,
                'audit_type' => $auditRequest->audit_type,
                'status' => $auditRequest->status,
                'property_state' => $auditRequest->property_state,
                'property_address' => $auditRequest->property_address,
                'created_at' => $auditRequest->created_at->toIso8601String(),
            ], 'Audit request submitted successfully');

        } catch (ValidationException $e) {
            Log::error('Audit request validation failed', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error submitting audit request: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'sql' => $e->getSql() ?? 'N/A',
                'bindings' => $e->getBindings() ?? []
            ]);
            return ResponseHelper::error('Database error: Failed to submit audit request', 500);
        } catch (\Exception $e) {
            Log::error('Error submitting audit request: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);
            return ResponseHelper::error('Failed to submit audit request: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get audit request status
     * GET /api/audit/request/{id}
     */
    public function getStatus($id)
    {
        try {
            $auditRequest = AuditRequest::where('id', $id)
                ->where('user_id', Auth::id())
                ->with(['order', 'approver:id,first_name,sur_name,email'])
                ->first();

            if (!$auditRequest) {
                return ResponseHelper::error('Audit request not found', 404);
            }

            return ResponseHelper::success([
                'id' => $auditRequest->id,
                'audit_type' => $auditRequest->audit_type,
                'status' => $auditRequest->status,
                'property_state' => $auditRequest->property_state,
                'property_address' => $auditRequest->property_address,
                'property_landmark' => $auditRequest->property_landmark,
                'property_floors' => $auditRequest->property_floors,
                'property_rooms' => $auditRequest->property_rooms,
                'is_gated_estate' => $auditRequest->is_gated_estate,
                'estate_name' => $auditRequest->estate_name,
                'estate_address' => $auditRequest->estate_address,
                'admin_notes' => $auditRequest->admin_notes,
                'approved_by' => $auditRequest->approver ? [
                    'id' => $auditRequest->approver->id,
                    'name' => $auditRequest->approver->first_name . ' ' . $auditRequest->approver->sur_name,
                    'email' => $auditRequest->approver->email,
                ] : null,
                'approved_at' => $auditRequest->approved_at?->toIso8601String(),
                'order_id' => $auditRequest->order_id,
                'created_at' => $auditRequest->created_at->toIso8601String(),
            ], 'Audit request retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Error fetching audit request: ' . $e->getMessage());
            return ResponseHelper::error('Failed to fetch audit request', 500);
        }
    }

    /**
     * Get all audit requests for the authenticated user
     * GET /api/audit/requests
     */
    public function index()
    {
        try {
            $auditRequests = AuditRequest::where('user_id', Auth::id())
                ->with(['order:id,order_number,total_price,payment_status'])
                ->latest()
                ->get();

            return ResponseHelper::success(
                $auditRequests->map(function ($request) {
                    return [
                        'id' => $request->id,
                        'audit_type' => $request->audit_type,
                        'status' => $request->status,
                        'property_state' => $request->property_state,
                        'property_address' => $request->property_address,
                        'order_id' => $request->order_id,
                        'order_number' => $request->order?->order_number,
                        'created_at' => $request->created_at->toIso8601String(),
                    ];
                }),
                'Audit requests retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('Error fetching audit requests: ' . $e->getMessage());
            return ResponseHelper::error('Failed to fetch audit requests', 500);
        }
    }
}
