<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Guarantor;
use App\Models\LoanApplication;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BNPLAdminController extends Controller
{
    /**
     * Get all BNPL applications
     * GET /api/admin/bnpl/applications
     */
    public function index(Request $request)
    {
        try {
            $query = LoanApplication::with(['user', 'guarantor', 'mono'])
                ->whereNotNull('customer_type'); // Filter BNPL applications

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by customer type
            if ($request->has('customer_type')) {
                $query->where('customer_type', $request->customer_type);
            }

            // Search by user name or email
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('user', function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('sur_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $applications = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return ResponseHelper::success($applications, 'BNPL applications retrieved successfully');
        } catch (Exception $e) {
            Log::error('BNPL Admin Index Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to retrieve BNPL applications', 500);
        }
    }

    /**
     * Get single BNPL application details
     * GET /api/admin/bnpl/applications/{id}
     */
    public function show($id)
    {
        try {
            $application = LoanApplication::with([
                'user',
                'guarantor',
                'mono',
                'mono.loanCalculation'
            ])->find($id);

            if (!$application) {
                return ResponseHelper::error('BNPL application not found', 404);
            }

            return ResponseHelper::success($application, 'BNPL application retrieved successfully');
        } catch (Exception $e) {
            Log::error('BNPL Admin Show Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to retrieve BNPL application', 500);
        }
    }

    /**
     * Update BNPL application status
     * PUT /api/admin/bnpl/applications/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,approved,rejected,counter_offer',
                'counter_offer_min_deposit' => 'required_if:status,counter_offer|numeric|min:0',
                'counter_offer_min_tenor' => 'required_if:status,counter_offer|integer|in:3,6,9,12',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            $application = LoanApplication::find($id);
            if (!$application) {
                return ResponseHelper::error('BNPL application not found', 404);
            }

            $application->status = $request->status;
            if ($request->has('admin_notes')) {
                $application->admin_notes = $request->admin_notes;
            }
            $application->save();

            // If counter offer, store the counter offer details
            if ($request->status === 'counter_offer') {
                // You might want to create a separate counter_offers table
                // For now, we'll store it in the application
                $application->counter_offer_min_deposit = $request->counter_offer_min_deposit;
                $application->counter_offer_min_tenor = $request->counter_offer_min_tenor;
                $application->save();
            }

            return ResponseHelper::success($application, 'BNPL application status updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('BNPL Admin Update Status Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to update BNPL application status', 500);
        }
    }

    /**
     * Get all guarantors
     * GET /api/admin/bnpl/guarantors
     */
    public function getGuarantors(Request $request)
    {
        try {
            $query = Guarantor::with(['user', 'loanApplication']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $guarantors = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            return ResponseHelper::success($guarantors, 'Guarantors retrieved successfully');
        } catch (Exception $e) {
            Log::error('BNPL Admin Get Guarantors Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to retrieve guarantors', 500);
        }
    }

    /**
     * Update guarantor status
     * PUT /api/admin/bnpl/guarantors/{id}/status
     */
    public function updateGuarantorStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,approved,rejected',
                'admin_notes' => 'nullable|string|max:1000',
            ]);

            $guarantor = Guarantor::find($id);
            if (!$guarantor) {
                return ResponseHelper::error('Guarantor not found', 404);
            }

            $guarantor->status = $request->status;
            if ($request->has('admin_notes')) {
                $guarantor->admin_notes = $request->admin_notes;
            }
            $guarantor->save();

            return ResponseHelper::success($guarantor, 'Guarantor status updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('BNPL Admin Update Guarantor Status Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to update guarantor status', 500);
        }
    }
}

