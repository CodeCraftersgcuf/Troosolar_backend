<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Models\State;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    /**
     * Get customer types for BNPL/Buy Now flow
     */
    public function getCustomerTypes()
    {
        $customerTypes = [
            ['id' => 'residential', 'label' => 'For Residential'],
            ['id' => 'sme', 'label' => 'For SMEs'],
            ['id' => 'commercial', 'label' => 'For Commercial and Industrial'],
        ];

        return ResponseHelper::success($customerTypes, 'Customer types retrieved successfully');
    }

    /**
     * Get audit types for professional audit flow
     */
    public function getAuditTypes()
    {
        $auditTypes = [
            ['id' => 'home-office', 'label' => 'Home / Office'],
            ['id' => 'commercial', 'label' => 'Commercial / Industrial'],
        ];

        return ResponseHelper::success($auditTypes, 'Audit types retrieved successfully');
    }

    /**
     * Get all active states
     */
    public function getStates()
    {
        try {
            $states = State::where('is_active', true)
                ->select('id', 'name', 'code')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get()
                ->map(function ($state) {
                    return [
                        'id' => $state->id,
                        'name' => $state->name,
                        'code' => $state->code,
                    ];
                });

            return ResponseHelper::success($states, 'States retrieved successfully');
        } catch (\Exception $e) {
            // If states table doesn't exist or has no data, return empty array
            return ResponseHelper::success([], 'No states available');
        }
    }

    /**
     * Get loan configuration for calculator
     * GET /api/config/loan-configuration
     */
    public function getLoanConfiguration()
    {
        try {
            $config = \App\Models\LoanConfiguration::where('is_active', true)->first();
            
            if (!$config) {
                // Return default values if no configuration exists
                return ResponseHelper::success([
                    'minimum_loan_amount' => 1500000,
                    'equity_contribution_min' => 30,
                    'equity_contribution_max' => 80,
                    'interest_rate_min' => 3,
                    'interest_rate_max' => 4,
                    'management_fee_percentage' => 1.0,
                    'residual_fee_percentage' => 1.0,
                    'insurance_fee_percentage' => 0.5,
                    'repayment_tenor_min' => 1,
                    'repayment_tenor_max' => 12,
                ], 'Loan configuration retrieved successfully (defaults)');
            }

            return ResponseHelper::success([
                'minimum_loan_amount' => (float) $config->minimum_loan_amount,
                'equity_contribution_min' => (float) $config->equity_contribution_min,
                'equity_contribution_max' => (float) $config->equity_contribution_max,
                'interest_rate_min' => (float) $config->interest_rate_min,
                'interest_rate_max' => (float) $config->interest_rate_max,
                'management_fee_percentage' => (float) $config->management_fee_percentage,
                'residual_fee_percentage' => (float) $config->residual_fee_percentage,
                'insurance_fee_percentage' => (float) $config->insurance_fee_percentage,
                'repayment_tenor_min' => (int) $config->repayment_tenor_min,
                'repayment_tenor_max' => (int) $config->repayment_tenor_max,
            ], 'Loan configuration retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve loan configuration', 500);
        }
    }

    /**
     * Get add-ons
     * GET /api/config/add-ons
     */
    public function getAddOns()
    {
        try {
            $addOns = \App\Models\AddOn::where('is_active', true)
                ->select('id', 'title', 'description', 'price', 'is_compulsory')
                ->orderBy('sort_order')
                ->orderBy('title')
                ->get();

            return ResponseHelper::success($addOns, 'Add-ons retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::success([], 'No add-ons available');
        }
    }

    /**
     * Get delivery locations by state
     * GET /api/config/delivery-locations?state_id={id}
     */
    public function getDeliveryLocations(Request $request)
    {
        try {
            $stateId = $request->query('state_id');
            
            $query = \App\Models\DeliveryLocation::where('is_active', true);
            
            if ($stateId) {
                $query->where('state_id', $stateId);
            }

            $locations = $query->select('id', 'name', 'state_id', 'delivery_fee', 'installation_fee')
                ->orderBy('name')
                ->get();

            return ResponseHelper::success($locations, 'Delivery locations retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::success([], 'No delivery locations available');
        }
    }
}
