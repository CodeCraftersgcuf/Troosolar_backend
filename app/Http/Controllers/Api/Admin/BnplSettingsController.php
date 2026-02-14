<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\BnplSettings;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BnplSettingsController extends Controller
{
    /**
     * Get BNPL global settings (single row).
     * GET /api/admin/bnpl/settings
     */
    public function show()
    {
        try {
            $settings = BnplSettings::get();
            return ResponseHelper::success([
                'interest_rate_percentage' => (float) $settings->interest_rate_percentage,
                'min_down_percentage' => (float) $settings->min_down_percentage,
                'management_fee_percentage' => (float) $settings->management_fee_percentage,
                'legal_fee_percentage' => (float) $settings->legal_fee_percentage,
                'insurance_fee_percentage' => (float) $settings->insurance_fee_percentage,
                'minimum_loan_amount' => (float) $settings->minimum_loan_amount,
                'loan_durations' => $settings->loan_durations ?? [3, 6, 9, 12],
            ], 'BNPL settings retrieved successfully');
        } catch (Exception $e) {
            Log::error('BNPL Settings Show Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to retrieve BNPL settings', 500);
        }
    }

    /**
     * Update BNPL global settings.
     * PUT /api/admin/bnpl/settings
     */
    public function update(Request $request)
    {
        try {
            $request->validate([
                'interest_rate_percentage' => 'nullable|numeric|min:0|max:100',
                'min_down_percentage' => 'nullable|numeric|min:0|max:100',
                'management_fee_percentage' => 'nullable|numeric|min:0|max:100',
                'legal_fee_percentage' => 'nullable|numeric|min:0|max:100',
                'insurance_fee_percentage' => 'nullable|numeric|min:0|max:100',
                'minimum_loan_amount' => 'nullable|numeric|min:0',
                'loan_durations' => 'nullable|array',
                'loan_durations.*' => 'integer|min:1|max:120',
            ]);

            $settings = BnplSettings::get();

            if ($request->has('interest_rate_percentage')) {
                $settings->interest_rate_percentage = $request->interest_rate_percentage;
            }
            if ($request->has('min_down_percentage')) {
                $settings->min_down_percentage = $request->min_down_percentage;
            }
            if ($request->has('management_fee_percentage')) {
                $settings->management_fee_percentage = $request->management_fee_percentage;
            }
            if ($request->has('legal_fee_percentage')) {
                $settings->legal_fee_percentage = $request->legal_fee_percentage;
            }
            if ($request->has('insurance_fee_percentage')) {
                $settings->insurance_fee_percentage = $request->insurance_fee_percentage;
            }
            if ($request->has('minimum_loan_amount')) {
                $settings->minimum_loan_amount = $request->minimum_loan_amount;
            }
            if ($request->has('loan_durations')) {
                $durations = $request->loan_durations;
                sort($durations);
                $settings->loan_durations = array_values(array_unique($durations));
            }

            $settings->save();

            return ResponseHelper::success([
                'interest_rate_percentage' => (float) $settings->interest_rate_percentage,
                'min_down_percentage' => (float) $settings->min_down_percentage,
                'management_fee_percentage' => (float) $settings->management_fee_percentage,
                'legal_fee_percentage' => (float) $settings->legal_fee_percentage,
                'insurance_fee_percentage' => (float) $settings->insurance_fee_percentage,
                'minimum_loan_amount' => (float) $settings->minimum_loan_amount,
                'loan_durations' => $settings->loan_durations ?? [3, 6, 9, 12],
            ], 'BNPL settings updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('BNPL Settings Update Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to update BNPL settings', 500);
        }
    }
}
