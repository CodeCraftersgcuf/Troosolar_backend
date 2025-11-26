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
}
