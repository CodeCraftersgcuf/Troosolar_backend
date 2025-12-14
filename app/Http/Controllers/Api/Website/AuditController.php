<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Models\AuditRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
            // Validate required fields
            // For home-office: property_state and property_address are required
            // For commercial: all property details are optional (admin will gather details)
            $data = $request->validate([
                'audit_type' => 'required|in:home-office,commercial',
                'customer_type' => 'nullable|in:residential,sme,commercial',
                // Property fields - required for home-office, optional for commercial
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

            // Verify user exists in database (foreign key constraint)
            $user = User::find($userId);
            if (!$user) {
                Log::error('User not found for audit request', ['user_id' => $userId]);
                return ResponseHelper::error('User account not found', 404);
            }

            // Check if audit_requests table exists
            if (!Schema::hasTable('audit_requests')) {
                Log::error('audit_requests table does not exist');
                return ResponseHelper::error('Database table not found. Please run migrations.', 500);
            }

            // Set status to pending for all audit types initially
            $status = 'pending';

            // Prepare data for creation - ensure proper types
            $auditData = [
                'user_id' => (int) $userId,
                'audit_type' => (string) $data['audit_type'],
                'status' => (string) $status,
                'customer_type' => !empty($data['customer_type']) ? (string) $data['customer_type'] : null,
                'property_state' => !empty($data['property_state']) ? (string) $data['property_state'] : null,
                'property_address' => !empty($data['property_address']) ? (string) $data['property_address'] : null,
                'property_landmark' => !empty($data['property_landmark']) ? (string) $data['property_landmark'] : null,
                'property_floors' => isset($data['property_floors']) && $data['property_floors'] !== '' ? (int) $data['property_floors'] : null,
                'property_rooms' => isset($data['property_rooms']) && $data['property_rooms'] !== '' ? (int) $data['property_rooms'] : null,
                'is_gated_estate' => filter_var($data['is_gated_estate'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'estate_name' => !empty($data['estate_name']) ? (string) $data['estate_name'] : null,
                'estate_address' => !empty($data['estate_address']) ? (string) $data['estate_address'] : null,
            ];

            // Log the data being inserted for debugging
            Log::info('Creating audit request', [
                'user_id' => $userId,
                'audit_data' => $auditData
            ]);

            // Create audit request using DB transaction for safety
            try {
                DB::beginTransaction();
                $auditRequest = AuditRequest::create($auditData);
                DB::commit();
            } catch (\Exception $createException) {
                DB::rollBack();
                throw $createException; // Re-throw to be caught by outer catch
            }

            if (!$auditRequest) {
                Log::error('Audit request creation returned null', ['data' => $auditData]);
                return ResponseHelper::error('Failed to create audit request', 500);
            }

            $message = $auditRequest->audit_type === 'commercial' 
                ? 'Commercial audit request submitted successfully. Admin will contact you for property details.'
                : 'Audit request submitted successfully';

            return ResponseHelper::success([
                'id' => $auditRequest->id,
                'audit_type' => $auditRequest->audit_type,
                'customer_type' => $auditRequest->customer_type,
                'status' => $auditRequest->status,
                'property_state' => $auditRequest->property_state,
                'property_address' => $auditRequest->property_address,
                'property_landmark' => $auditRequest->property_landmark,
                'property_floors' => $auditRequest->property_floors,
                'property_rooms' => $auditRequest->property_rooms,
                'is_gated_estate' => $auditRequest->is_gated_estate,
                'estate_name' => $auditRequest->estate_name,
                'estate_address' => $auditRequest->estate_address,
                'has_property_details' => !empty($auditRequest->property_address), // Indicates if user provided details
                'created_at' => $auditRequest->created_at->toIso8601String(),
            ], $message);

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
            $errorCode = $e->getCode();
            $errorMessage = $e->getMessage();
            
            Log::error('Database error submitting audit request: ' . $errorMessage, [
                'error_code' => $errorCode,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'sql' => $e->getSql() ?? 'N/A',
                'bindings' => $e->getBindings() ?? []
            ]);
            
            // Provide more specific error messages
            if (strpos($errorMessage, 'foreign key constraint') !== false) {
                if (strpos($errorMessage, 'user_id') !== false) {
                    return ResponseHelper::error('Invalid user account. Please log in again.', 400);
                } elseif (strpos($errorMessage, 'order_id') !== false) {
                    return ResponseHelper::error('Invalid order reference.', 400);
                }
                return ResponseHelper::error('Database constraint violation. Please check your data.', 400);
            }
            
            if (strpos($errorMessage, "doesn't exist") !== false || strpos($errorMessage, 'Unknown column') !== false) {
                return ResponseHelper::error('Database schema error. Please contact support.', 500);
            }
            
            return ResponseHelper::error('Database error: ' . $errorMessage, 500);
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
