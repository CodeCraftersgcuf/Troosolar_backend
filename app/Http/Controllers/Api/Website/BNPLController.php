<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Guarantor;
use App\Models\LoanApplication;
use App\Models\LoanCalculation;
use App\Models\MonoLoanCalculation;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BNPLController extends Controller
{
    // Minimum loan amount for BNPL (configurable)
    private const MIN_LOAN_AMOUNT = 1500000; // ₦1,500,000

    /**
     * POST /api/bnpl/apply
     * Submit BNPL loan application
     */
    public function apply(Request $request)
    {
        try {
            // Normalize request data - handle FormData bracket notation and trim BVN
            $allInput = $request->all();

            // Convert FormData bracket notation to nested arrays
            $personalDetails = [];
            $propertyDetails = [];

            foreach ($allInput as $key => $value) {
                // Handle personal_details[field] notation
                if (preg_match('/^personal_details\[(.+)\]$/', $key, $matches)) {
                    $fieldName = $matches[1];
                    $personalDetails[$fieldName] = $value;
                    unset($allInput[$key]);
                }
                // Handle property_details[field] notation
                elseif (preg_match('/^property_details\[(.+)\]$/', $key, $matches)) {
                    $fieldName = $matches[1];
                    $propertyDetails[$fieldName] = $value;
                    unset($allInput[$key]);
                }
            }

            // Merge converted nested arrays back
            if (!empty($personalDetails)) {
                $allInput['personal_details'] = array_merge($allInput['personal_details'] ?? [], $personalDetails);
            }
            if (!empty($propertyDetails)) {
                $allInput['property_details'] = array_merge($allInput['property_details'] ?? [], $propertyDetails);
            }

            // Trim BVN if it exists (handle both nested and flat formats)
            if (isset($allInput['personal_details']['bvn'])) {
                $allInput['personal_details']['bvn'] = preg_replace('/\s+/', '', trim((string) $allInput['personal_details']['bvn']));
            } elseif (isset($allInput['bvn'])) {
                $allInput['bvn'] = preg_replace('/\s+/', '', trim((string) $allInput['bvn']));
            }

            // Merge normalized data back into request
            $request->merge($allInput);

            // Validate required fields - handle both JSON and FormData formats
            $validationRules = [
                'customer_type' => 'required|in:residential,sme,commercial',
                'product_category' => 'required|string',
                'loan_amount' => 'required|numeric|min:1500000',
                'repayment_duration' => 'required|in:3,6,9,12',
                'credit_check_method' => 'required|in:auto,manual',
                'bank_statement' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'live_photo' => 'required|file|mimes:jpg,jpeg,png|max:5120',
            ];

            // Handle nested arrays - check if data comes as nested or flat (after normalization)
            $hasNestedPersonal = isset($allInput['personal_details']) && is_array($allInput['personal_details']);

            if ($hasNestedPersonal) {
                // If personal_details is sent as nested array (JSON or FormData with brackets)
                $validationRules['personal_details'] = 'required|array';
                $validationRules['personal_details.full_name'] = 'required|string|max:255';
                $validationRules['personal_details.bvn'] = 'required|string';
                $validationRules['personal_details.phone'] = 'required|string|max:20';
                $validationRules['personal_details.email'] = 'required|email|max:255';
                $validationRules['personal_details.social_media'] = 'required|string|max:255'; // COMPULSORY
            } else {
                // If sent as flat fields
                $validationRules['full_name'] = 'required|string|max:255';
                $validationRules['bvn'] = 'required|string';
                $validationRules['phone'] = 'required|string|max:20';
                $validationRules['email'] = 'required|email|max:255';
                $validationRules['social_media'] = 'required|string|max:255'; // COMPULSORY
            }

            // Normalize property_details bracket notation if present
            if (isset($allInput['property_details[state]'])) {
                if (!isset($allInput['property_details'])) {
                    $allInput['property_details'] = [];
                }
                $allInput['property_details']['state'] = $allInput['property_details[state]'] ?? null;
                $allInput['property_details']['address'] = $allInput['property_details[address]'] ?? null;
                $allInput['property_details']['is_gated_estate'] = $allInput['property_details[is_gated_estate]'] ?? false;
                $allInput['property_details']['landmark'] = $allInput['property_details[landmark]'] ?? null;
                $allInput['property_details']['floors'] = $allInput['property_details[floors]'] ?? null;
                $allInput['property_details']['rooms'] = $allInput['property_details[rooms]'] ?? null;
                $allInput['property_details']['estate_name'] = $allInput['property_details[estate_name]'] ?? null;
                $allInput['property_details']['estate_address'] = $allInput['property_details[estate_address]'] ?? null;
                // Remove bracket notation keys
                foreach ($allInput as $key => $value) {
                    if (strpos($key, 'property_details[') === 0) {
                        unset($allInput[$key]);
                    }
                }
                $request->merge($allInput);
            }

            $hasNestedProperty = isset($allInput['property_details']) && is_array($allInput['property_details']);

            if ($hasNestedProperty) {
                // If property_details is sent as nested array
                $validationRules['property_details'] = 'required|array';
                $validationRules['property_details.state'] = 'required|string|max:100';
                $validationRules['property_details.address'] = 'required|string';
                $validationRules['property_details.is_gated_estate'] = 'required|boolean';
                $validationRules['property_details.landmark'] = 'nullable|string|max:255';
                $validationRules['property_details.floors'] = 'nullable|integer|min:1';
                $validationRules['property_details.rooms'] = 'nullable|integer|min:1';
            } else {
                // If sent as flat fields
                $validationRules['property_state'] = 'required|string|max:100';
                $validationRules['property_address'] = 'required|string';
                $validationRules['is_gated_estate'] = 'required|boolean';
                $validationRules['property_landmark'] = 'nullable|string|max:255';
                $validationRules['property_floors'] = 'nullable|integer|min:1';
                $validationRules['property_rooms'] = 'nullable|integer|min:1';
            }

            $data = $request->validate($validationRules);

            // Validate minimum loan amount
            $loanAmount = (float) $data['loan_amount'];
            if ($loanAmount < self::MIN_LOAN_AMOUNT) {
                return ResponseHelper::error(
                    "Your order total does not meet the minimum ₦" . number_format(self::MIN_LOAN_AMOUNT) . " amount required for credit financing. To qualify for Buy Now, Pay Later, please add more items to your cart. Thank you.",
                    422
                );
            }

            // Extract personal details (handle both formats)
            $personalDetails = $data['personal_details'] ?? [
                'full_name' => $data['full_name'] ?? null,
                'bvn' => $data['bvn'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'social_media' => $data['social_media'] ?? null,
            ];

            // Ensure BVN is clean (should already be trimmed, but double-check)
            if (isset($personalDetails['bvn'])) {
                $personalDetails['bvn'] = preg_replace('/\s+/', '', trim($personalDetails['bvn']));
                // if (strlen($personalDetails['bvn']) !== 11 || !preg_match('/^\d{11}$/', $personalDetails['bvn'])) {
                //     return ResponseHelper::error('BVN must be exactly 11 digits (numbers only)', 422);
                // }
            }

            // Extract property details (handle both formats)
            $propertyDetails = $data['property_details'] ?? [
                'state' => $data['property_state'] ?? null,
                'address' => $data['property_address'] ?? null,
                'landmark' => $data['property_landmark'] ?? null,
                'floors' => $data['property_floors'] ?? null,
                'rooms' => $data['property_rooms'] ?? null,
                'is_gated_estate' => $data['is_gated_estate'] ?? false,
                'estate_name' => $data['estate_name'] ?? null,
                'estate_address' => $data['estate_address'] ?? null,
            ];

            // Validate gated estate fields if is_gated_estate is true
            if (($propertyDetails['is_gated_estate'] ?? false) == true) {
                $request->validate([
                    'property_details.estate_name' => 'required_with:property_details|string|max:255',
                    'property_details.estate_address' => 'required_with:property_details|string',
                    'estate_name' => 'required_without:property_details|string|max:255',
                    'estate_address' => 'required_without:property_details|string',
                ]);

                if (isset($propertyDetails['estate_name']) && empty($propertyDetails['estate_name'])) {
                    return ResponseHelper::error('Estate name is required when gated estate is selected', 422);
                }
                if (isset($propertyDetails['estate_address']) && empty($propertyDetails['estate_address'])) {
                    return ResponseHelper::error('Estate address is required when gated estate is selected', 422);
                }
            }

            // Handle file uploads
            $bankStatementPath = null;
            $livePhotoPath = null;

            if ($request->hasFile('bank_statement')) {
                $file = $request->file('bank_statement');
                $ext = $file->getClientOriginalExtension();
                $fileName = 'bank_statement_' . time() . '.' . $ext;
                $file->move(public_path('/loan_applications'), $fileName);
                $bankStatementPath = 'loan_applications/' . $fileName;
            }

            if ($request->hasFile('live_photo')) {
                $file = $request->file('live_photo');
                $ext = $file->getClientOriginalExtension();
                $fileName = 'live_photo_' . time() . '.' . $ext;
                $file->move(public_path('/loan_applications'), $fileName);
                $livePhotoPath = 'loan_applications/' . $fileName;
            }

            // Get or create loan calculation
            $loanCalculation = LoanCalculation::where('user_id', Auth::id())
                ->where('status', 'calculated')
                ->latest()
                ->first();

            // Get or create MonoLoanCalculation if loan calculation exists
            $monoLoanCalculationId = null;
            if ($loanCalculation) {
                // Check if MonoLoanCalculation exists for this LoanCalculation
                $monoLoanCalculation = MonoLoanCalculation::where('loan_calculation_id', $loanCalculation->id)->first();
                
                if (!$monoLoanCalculation) {
                    // Create MonoLoanCalculation if it doesn't exist
                    $monoLoanCalculation = MonoLoanCalculation::create([
                        'loan_calculation_id' => $loanCalculation->id,
                        'loan_amount' => $loanAmount,
                        'repayment_duration' => $data['repayment_duration'] ?? $loanCalculation->repayment_duration,
                        'down_payment' => $loanAmount * 0.30, // 30% default
                        'total_amount' => $loanAmount,
                        'status' => 'pending',
                    ]);
                }
                
                $monoLoanCalculationId = $monoLoanCalculation->id;
                
                // Update loan calculation status
                $loanCalculation->status = 'submitted';
                $loanCalculation->save();
            }

            // Create loan application
            $loanApplication = LoanApplication::create([
                'user_id' => Auth::id(),
                'mono_loan_calculation' => $monoLoanCalculationId, // Can be null if no loan calculation exists
                'loan_amount' => $loanAmount,
                'repayment_duration' => $data['repayment_duration'] ?? null,
                'customer_type' => $data['customer_type'] ?? null,
                'product_category' => $data['product_category'] ?? null,
                'audit_type' => $data['audit_type'] ?? null,
                'property_state' => $propertyDetails['state'] ?? null,
                'property_address' => $propertyDetails['address'] ?? null,
                'property_landmark' => $propertyDetails['landmark'] ?? null,
                'property_floors' => $propertyDetails['floors'] ?? null,
                'property_rooms' => $propertyDetails['rooms'] ?? null,
                'is_gated_estate' => $propertyDetails['is_gated_estate'] ?? false,
                'estate_name' => $propertyDetails['estate_name'] ?? null,
                'estate_address' => $propertyDetails['estate_address'] ?? null,
                'credit_check_method' => $data['credit_check_method'] ?? 'auto',
                'bank_statement_path' => $bankStatementPath,
                'live_photo_path' => $livePhotoPath,
                'social_media_handle' => $personalDetails['social_media'] ?? null,
                'status' => 'pending',
            ]);

            return ResponseHelper::success([
                'loan_application' => $loanApplication,
                'message' => 'BNPL application submitted successfully. You will receive feedback within 24-48 hours.'
            ], 'BNPL application submitted successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return 422 for validation errors
            Log::warning('BNPL Application Validation Error', ['errors' => $e->errors()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('BNPL Application Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to submit BNPL application: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/bnpl/applications
     * Get all BNPL applications for the authenticated user
     */
    public function getApplications(Request $request)
    {
        try {
            $userId = Auth::id();
            
            $query = LoanApplication::with([
                'mono',
                'guarantor:id,loan_application_id,full_name,status',
            ])
            ->where('user_id', $userId);

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Sort by latest first
            $applications = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            $formattedData = $applications->getCollection()->map(function ($application) {
                return [
                    'id' => $application->id,
                    'customer_type' => $application->customer_type,
                    'product_category' => $application->product_category,
                    'loan_amount' => number_format((float) $application->loan_amount, 2),
                    'repayment_duration' => $application->repayment_duration,
                    'status' => $application->status, // pending, approved, rejected, counter_offer
                    'property_state' => $application->property_state,
                    'property_address' => $application->property_address,
                    'is_gated_estate' => $application->is_gated_estate,
                    'guarantor' => $application->guarantor ? [
                        'id' => $application->guarantor->id,
                        'full_name' => $application->guarantor->full_name,
                        'status' => $application->guarantor->status,
                    ] : null,
                    'order' => null, // Order relationship will be added if needed
                    'created_at' => $application->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $application->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return ResponseHelper::success([
                'data' => $formattedData,
                'pagination' => [
                    'current_page' => $applications->currentPage(),
                    'last_page' => $applications->lastPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                    'from' => $applications->firstItem(),
                    'to' => $applications->lastItem(),
                ],
            ], 'BNPL applications retrieved successfully');

        } catch (Exception $e) {
            Log::error('BNPL Applications List Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to retrieve BNPL applications: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/bnpl/status/{application_id}
     * Get BNPL application status with detailed information
     */
    public function getStatus($applicationId)
    {
        try {
            $application = LoanApplication::with([
                'mono',
                'guarantor:id,loan_application_id,full_name,email,phone,status,signed_form_path',
            ])
            ->where('id', $applicationId)
            ->where('user_id', Auth::id())
            ->first();

            if (!$application) {
                return ResponseHelper::error('Application not found', 404);
            }

            // Get loan calculation details if available
            $loanCalculationDetails = null;
            if ($application->mono_loan_calculation && $application->mono) {
                $monoLoan = $application->mono;
                $loanCalculationDetails = [
                    'loan_amount' => number_format((float) ($monoLoan->loan_amount ?? $application->loan_amount), 2),
                    'repayment_duration' => $monoLoan->repayment_duration ?? $application->repayment_duration,
                    'down_payment' => number_format((float) ($monoLoan->down_payment ?? 0), 2),
                    'total_amount' => number_format((float) ($monoLoan->total_amount ?? 0), 2),
                    'interest_rate' => $monoLoan->interest_rate ?? null,
                ];
            }

            return ResponseHelper::success([
                'id' => $application->id,
                'customer_type' => $application->customer_type,
                'product_category' => $application->product_category,
                'loan_amount' => number_format((float) $application->loan_amount, 2),
                'repayment_duration' => $application->repayment_duration,
                'status' => $application->status, // pending, approved, rejected, counter_offer, counter_offer_accepted
                'property_state' => $application->property_state,
                'property_address' => $application->property_address,
                'property_landmark' => $application->property_landmark,
                'property_floors' => $application->property_floors,
                'property_rooms' => $application->property_rooms,
                'is_gated_estate' => $application->is_gated_estate,
                'estate_name' => $application->estate_name,
                'estate_address' => $application->estate_address,
                'credit_check_method' => $application->credit_check_method,
                'social_media_handle' => $application->social_media_handle,
                'bank_statement_path' => $application->bank_statement_path,
                'live_photo_path' => $application->live_photo_path,
                'loan_calculation' => $loanCalculationDetails,
                'guarantor' => $application->guarantor ? [
                    'id' => $application->guarantor->id,
                    'full_name' => $application->guarantor->full_name,
                    'email' => $application->guarantor->email,
                    'phone' => $application->guarantor->phone,
                    'status' => $application->guarantor->status,
                    'has_signed_form' => !empty($application->guarantor->signed_form_path),
                ] : null,
                'created_at' => $application->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $application->updated_at->format('Y-m-d H:i:s'),
            ], 'Application status retrieved successfully');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('BNPL Status - Application not found: ' . $e->getMessage());
            return ResponseHelper::error('Application not found', 404);
        } catch (Exception $e) {
            Log::error('BNPL Status Error: ' . $e->getMessage(), [
                'application_id' => $applicationId,
                'trace' => $e->getTraceAsString()
            ]);
            return ResponseHelper::error('Failed to retrieve application status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/bnpl/guarantor/invite
     * Invite or add guarantor details
     */
    public function inviteGuarantor(Request $request)
    {
        try {
            $data = $request->validate([
                'loan_application_id' => 'required|exists:loan_applications,id',
                'full_name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'required|string|max:20',
                'bvn' => 'nullable|string|size:11',
                'relationship' => 'nullable|string|max:100',
            ]);

            $application = LoanApplication::where('id', $data['loan_application_id'])
                ->where('user_id', Auth::id())
                ->first();

            if (!$application) {
                return ResponseHelper::error('Loan application not found', 404);
            }

            $guarantor = Guarantor::create([
                'user_id' => Auth::id(),
                'loan_application_id' => $application->id,
                'full_name' => $data['full_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'],
                'bvn' => $data['bvn'] ?? null,
                'relationship' => $data['relationship'] ?? null,
                'status' => 'pending',
            ]);

            // Update loan application with guarantor_id
            $application->guarantor_id = $guarantor->id;
            $application->save();

            // TODO: Send email/SMS to guarantor if email/phone provided

            return ResponseHelper::success($guarantor, 'Guarantor details saved successfully');

        } catch (Exception $e) {
            Log::error('Guarantor Invite Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to save guarantor details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/bnpl/guarantor/upload
     * Upload signed guarantor form
     */
    public function uploadGuarantorForm(Request $request)
    {
        try {
            $data = $request->validate([
                'guarantor_id' => 'required|exists:guarantors,id',
                'signed_form' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            $guarantor = Guarantor::where('id', $data['guarantor_id'])
                ->where('user_id', Auth::id())
                ->first();

            if (!$guarantor) {
                return ResponseHelper::error('Guarantor not found', 404);
            }

            $file = $request->file('signed_form');
            $ext = $file->getClientOriginalExtension();
            $fileName = 'guarantor_form_' . $guarantor->id . '_' . time() . '.' . $ext;
            $file->move(public_path('/loan_applications'), $fileName);
            $filePath = 'loan_applications/' . $fileName;

            $guarantor->signed_form_path = $filePath;
            $guarantor->save();

            return ResponseHelper::success([
                'guarantor_id' => $guarantor->id,
                'signed_form_path' => $filePath,
            ], 'Guarantor form uploaded successfully');

        } catch (Exception $e) {
            Log::error('Guarantor Form Upload Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to upload guarantor form: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/bnpl/counteroffer/accept
     * Accept counteroffer from admin
     */
    public function acceptCounterOffer(Request $request)
    {
        try {
            $data = $request->validate([
                'application_id' => 'required|exists:loan_applications,id',
                'minimum_deposit' => 'required|numeric|min:0',
                'minimum_tenor' => 'required|integer|min:3',
            ]);

            $application = LoanApplication::where('id', $data['application_id'])
                ->where('user_id', Auth::id())
                ->first();

            if (!$application) {
                return ResponseHelper::error('Application not found', 404);
            }

            // Update application with counteroffer details
            $application->status = 'counter_offer_accepted';
            $application->save();

            // Update loan calculation with new terms
            $loanCalculation = LoanCalculation::where('id', $application->mono_loan_calculation)->first();
            if ($loanCalculation) {
                $loanCalculation->repayment_duration = $data['minimum_tenor'];
                $loanCalculation->save();
            }

            return ResponseHelper::success([
                'application_id' => $application->id,
                'minimum_deposit' => $data['minimum_deposit'],
                'minimum_tenor' => $data['minimum_tenor'],
            ], 'Counteroffer accepted successfully');

        } catch (Exception $e) {
            Log::error('Counteroffer Accept Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to accept counteroffer: ' . $e->getMessage(), 500);
        }
    }
}
