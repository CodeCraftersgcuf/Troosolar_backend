<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\Bundles;
use App\Models\Guarantor;
use App\Models\LoanApplication;
use App\Models\LoanCalculation;
use App\Models\MonoLoanCalculation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\LoanInstallment;
use App\Models\LoanRepayment;
use App\Models\BnplSettings;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BNPLController extends Controller
{
    // Minimum loan amount for BNPL (removed - no minimum requirement)
    // private const MIN_LOAN_AMOUNT = 1500000; // ₦1,500,000

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

            $settings = BnplSettings::get();
            $allowedDurations = $settings->loan_durations ?? [3, 6, 9, 12];
            // Validate required fields - handle both JSON and FormData formats
            $validationRules = [
                'customer_type' => 'required|in:residential,sme,commercial',
                'product_category' => 'required|string',
                'loan_amount' => 'required|numeric|min:0',
                'repayment_duration' => 'required|integer|in:' . implode(',', $allowedDurations),
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

            $data = $request->validate(array_merge($validationRules, [
                'bundle_ids' => 'nullable|array',
                'bundle_ids.*' => 'integer|exists:bundles,id',
                'product_ids' => 'nullable|array',
                'product_ids.*' => 'integer|exists:products,id',
            ]));

            // Get loan amount (minimum validation removed - no minimum requirement)
            $loanAmount = (float) $data['loan_amount'];

            // Build order_items_snapshot from bundle_ids and product_ids for creating order items when down payment is confirmed
            $orderItemsSnapshot = [];
            $bundleIds = $data['bundle_ids'] ?? [];
            $productIds = $data['product_ids'] ?? [];
            foreach ($bundleIds as $bundleId) {
                $bundle = Bundles::find($bundleId);
                if ($bundle) {
                    $unitPrice = (float) ($bundle->discount_price ?? $bundle->total_price ?? 0);
                    $orderItemsSnapshot[] = [
                        'itemable_type' => Bundles::class,
                        'itemable_id' => (int) $bundleId,
                        'quantity' => 1,
                        'unit_price' => $unitPrice,
                        'subtotal' => $unitPrice,
                    ];
                }
            }
            foreach ($productIds as $productId) {
                $product = Product::find($productId);
                if ($product) {
                    $unitPrice = (float) ($product->discount_price ?? $product->price ?? 0);
                    $orderItemsSnapshot[] = [
                        'itemable_type' => Product::class,
                        'itemable_id' => (int) $productId,
                        'quantity' => 1,
                        'unit_price' => $unitPrice,
                        'subtotal' => $unitPrice,
                    ];
                }
            }
            
            // Minimum loan amount validation removed - no minimum requirement
            // if ($loanAmount < self::MIN_LOAN_AMOUNT) {
            //     return ResponseHelper::error(
            //         "Your order total does not meet the minimum ₦" . number_format(self::MIN_LOAN_AMOUNT) . " amount required for credit financing. To qualify for Buy Now, Pay Later, please add more items to your cart. Thank you.",
            //         422
            //     );
            // }

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
                    $monoLoanCalculation = MonoLoanCalculation::create([
                        'loan_calculation_id' => $loanCalculation->id,
                        'loan_amount' => $loanAmount,
                        'repayment_duration' => (int) ($data['repayment_duration'] ?? $loanCalculation->repayment_duration),
                        'down_payment' => round($loanAmount * ((float) ($settings->min_down_percentage ?? 30) / 100), 2),
                        'total_amount' => $loanAmount,
                        'status' => 'pending',
                        'interest_rate' => $settings->interest_rate_percentage,
                        'management_fee_percentage' => $settings->management_fee_percentage,
                        'legal_fee_percentage' => $settings->legal_fee_percentage,
                        'insurance_fee_percentage' => $settings->insurance_fee_percentage,
                    ]);
                }
                
                $monoLoanCalculationId = $monoLoanCalculation->id;
                
                // Update loan calculation status
                $loanCalculation->status = 'submitted';
                $loanCalculation->save();
            }

            // Create loan application (with optional order_items_snapshot for multi-item BNPL orders)
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
                'order_items_snapshot' => !empty($orderItemsSnapshot) ? $orderItemsSnapshot : null,
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

            // If down payment was done, an order exists for this application's mono – return it so frontend can show order view
            $orderInfo = null;
            if ($application->mono_loan_calculation) {
                $existingOrder = Order::where('mono_calculation_id', $application->mono_loan_calculation)
                    ->where('user_id', Auth::id())
                    ->first();
                if ($existingOrder) {
                    $orderInfo = [
                        'order_id' => $existingOrder->id,
                        'order_number' => $existingOrder->order_number,
                        'down_payment_completed' => true,
                    ];
                }
            }

            // Calculate counter offer details if status is counter_offer
            $counterOfferDetails = null;
            if ($application->status === 'counter_offer' && 
                $application->counter_offer_min_deposit !== null && 
                $application->counter_offer_min_tenor !== null) {
                
                $monoLoan = $application->mono;
                if ($monoLoan) {
                    $loanAmount = (float) $monoLoan->loan_amount;
                    $interestRate = (float) ($monoLoan->interest_rate ?? 0);
                } else {
                    $loanAmount = (float) $application->loan_amount;
                    $interestRate = 0;
                }
                
                $downPayment = (float) $application->counter_offer_min_deposit;
                $duration = (int) $application->counter_offer_min_tenor;
                
                // Calculate total amount: loan amount + interest (same logic as acceptCounterOffer)
                $totalAmount = $loanAmount + ($loanAmount * $interestRate);
                
                // Calculate monthly payment: (total amount - down payment) / duration
                $monthlyPayment = $duration > 0 ? round(($totalAmount - $downPayment) / $duration, 2) : 0;
                
                $counterOfferDetails = [
                    'loan_amount' => number_format($loanAmount, 2),
                    'down_payment' => number_format($downPayment, 2),
                    'repayment_duration' => $duration,
                    'interest_rate' => $interestRate > 0 ? $interestRate : null,
                    'total_amount' => number_format($totalAmount, 2),
                    'monthly_payment' => number_format($monthlyPayment, 2),
                ];
            }

            return ResponseHelper::success([
                'id' => $application->id,
                'customer_type' => $application->customer_type,
                'product_category' => $application->product_category,
                'loan_amount' => number_format((float) $application->loan_amount, 2),
                'repayment_duration' => $application->repayment_duration,
                'status' => $application->status, // pending, approved, rejected, counter_offer, counter_offer_accepted
                'admin_notes' => $application->admin_notes,
                'counter_offer_min_deposit' => $application->counter_offer_min_deposit !== null ? (float) $application->counter_offer_min_deposit : null,
                'counter_offer_min_tenor' => $application->counter_offer_min_tenor,
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
                'counter_offer_details' => $counterOfferDetails,
                'guarantor' => $application->guarantor ? [
                    'id' => $application->guarantor->id,
                    'full_name' => $application->guarantor->full_name,
                    'email' => $application->guarantor->email,
                    'phone' => $application->guarantor->phone,
                    'status' => $application->guarantor->status,
                    'has_signed_form' => !empty($application->guarantor->signed_form_path),
                ] : null,
                'order_id' => $orderInfo ? $orderInfo['order_id'] : null,
                'order_number' => $orderInfo ? $orderInfo['order_number'] : null,
                'down_payment_completed' => $orderInfo ? $orderInfo['down_payment_completed'] : false,
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
     * GET /api/bnpl/guarantor/form
     * Download the guarantor form PDF (for customers to give to their guarantor).
     * Optional query: loan_application_id (to ensure user has an application).
     */
    public function downloadGuarantorForm(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            // Optional: ensure user has a BNPL application when loan_application_id is provided
            $applicationId = $request->query('loan_application_id');
            if ($applicationId) {
                $application = LoanApplication::where('id', $applicationId)
                    ->where('user_id', $user->id)
                    ->first();
                if (!$application) {
                    return response()->json(['message' => 'Loan application not found'], 404);
                }
            }

            // Form path: public/documents/guarantor-form.pdf or config
            $relativePath = config('bnpl.guarantor_form_path', 'documents/guarantor-form.pdf');
            $fullPath = public_path($relativePath);

            $filename = 'Troosolar-BNPL-Guarantor-Form.pdf';

            // Serve real file only if it exists, is readable, and has content (not empty)
            if (file_exists($fullPath) && is_readable($fullPath) && filesize($fullPath) > 0) {
                $content = file_get_contents($fullPath);
                return response($content, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                    'Content-Length' => (string) strlen($content),
                    'Content-Transfer-Encoding' => 'binary',
                    'Cache-Control' => 'no-transform, no-cache',
                ]);
            }

            // Fallback: serve placeholder PDF as raw binary (no temp file – avoids proxy/stream issues)
            Log::warning('Guarantor form file not found or empty, serving placeholder', ['path' => $fullPath]);
            $placeholderPdf = $this->getGuarantorFormPlaceholderPdf();
            if (strlen($placeholderPdf) === 0) {
                $placeholderPdf = $this->getMinimalPdfFallback();
            }
            return response($placeholderPdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => (string) strlen($placeholderPdf),
                'Content-Transfer-Encoding' => 'binary',
                'Cache-Control' => 'no-transform, no-cache',
            ]);
        } catch (Exception $e) {
            Log::error('Guarantor form download error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to download guarantor form'], 500);
        }
    }

    /**
     * Minimal valid PDF used when guarantor-form.pdf is not present.
     * Replace public/documents/guarantor-form.pdf with the real form to serve it instead.
     * Text strings use escaped parentheses \( \) so content displays correctly in viewers.
     */
    private function getGuarantorFormPlaceholderPdf(): string
    {
        $body = "%PDF-1.4\n";
        $o1 = strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $o2 = strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $o3 = strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources 4 0 R /MediaBox [0 0 612 792] /Contents 5 0 R >>\nendobj\n";
        $o4 = strlen($body);
        $body .= "4 0 obj\n<< /Font << /F1 6 0 R >> >>\nendobj\n";
        $o5 = strlen($body);
        // PDF text: parentheses in strings must be escaped as \( and \)
        $streamContent = "BT\n"
            . "/F1 18 Tf\n72 720 Td\n"
            . "\(Troosolar BNPL - Guarantor Form\) Tj\n"
            . "0 -28 Td\n"
            . "/F1 12 Tf\n"
            . "\(This is a placeholder form.\) Tj\n"
            . "0 -20 Td\n"
            . "/F1 10 Tf\n"
            . "\(To use your own form, place the PDF file at:\) Tj\n"
            . "0 -16 Td\n"
            . "\(public/documents/guarantor-form.pdf\) Tj\n"
            . "0 -24 Td\n"
            . "\(Signed guarantor documents and undated cheques will be collected on the day of installation.\) Tj\n"
            . "ET\n";
        $body .= "5 0 obj\n<< /Length " . strlen($streamContent) . " >>\nstream\n" . $streamContent . "endstream\nendobj\n";
        $o6 = strlen($body);
        $body .= "6 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $xref = strlen($body);
        $body .= "xref\n0 7\n";
        $body .= sprintf("%010d 65535 f \n", 0);
        $body .= sprintf("%010d 00000 n \n", $o1);
        $body .= sprintf("%010d 00000 n \n", $o2);
        $body .= sprintf("%010d 00000 n \n", $o3);
        $body .= sprintf("%010d 00000 n \n", $o4);
        $body .= sprintf("%010d 00000 n \n", $o5);
        $body .= sprintf("%010d 00000 n \n", $o6);
        $body .= "trailer\n<< /Size 7 /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
        return $body;
    }

    /**
     * Smallest valid PDF (single blank page) used if placeholder generation ever returns empty.
     */
    private function getMinimalPdfFallback(): string
    {
        $b = "%PDF-1.4\n";
        $o1 = strlen($b);
        $b .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $o2 = strlen($b);
        $b .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $o3 = strlen($b);
        $b .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] >>\nendobj\n";
        $xref = strlen($b);
        $b .= "xref\n0 4\n";
        $b .= sprintf("%010d 65535 f \n", 0);
        $b .= sprintf("%010d 00000 n \n", $o1);
        $b .= sprintf("%010d 00000 n \n", $o2);
        $b .= sprintf("%010d 00000 n \n", $o3);
        $b .= "trailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
        return $b;
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
     * Accept counteroffer from admin – update Mono + LoanCalculation and set status.
     */
    public function acceptCounterOffer(Request $request)
    {
        try {
            $data = $request->validate([
                'application_id' => 'required|exists:loan_applications,id',
                'minimum_deposit' => 'required|numeric|min:0',
                'minimum_tenor' => 'required|integer|in:3,6,9,12',
            ]);

            $application = LoanApplication::with('mono.loanCalculation')
                ->where('id', $data['application_id'])
                ->where('user_id', Auth::id())
                ->first();

            if (!$application) {
                return ResponseHelper::error('Application not found', 404);
            }

            if ($application->status !== 'counter_offer') {
                return ResponseHelper::error('This application does not have a counter offer to accept', 422);
            }

            $mono = $application->mono;
            if (!$mono) {
                return ResponseHelper::error('Loan calculation not found for this application', 404);
            }

            $downPayment = (float) $data['minimum_deposit'];
            $duration = (int) $data['minimum_tenor'];
            $loanAmount = (float) $mono->loan_amount;
            $interestRate = (float) ($mono->interest_rate ?? 0);
            $totalAmount = $loanAmount + ($loanAmount * $interestRate);
            $monthlyPayment = $duration > 0 ? round(($totalAmount - $downPayment) / $duration, 2) : 0;

            $mono->down_payment = $downPayment;
            $mono->repayment_duration = $duration;
            $mono->total_amount = $totalAmount;
            $mono->save();

            $application->repayment_duration = $duration;
            $application->status = 'counter_offer_accepted';
            $application->save();

            if ($mono->loanCalculation) {
                $mono->loanCalculation->repayment_duration = $duration;
                $mono->loanCalculation->monthly_payment = $monthlyPayment;
                $mono->loanCalculation->repayment_date = $mono->loanCalculation->repayment_date ?? now()->addMonth();
                $mono->loanCalculation->save();
            }

            return ResponseHelper::success([
                'application_id' => $application->id,
                'minimum_deposit' => $downPayment,
                'minimum_tenor' => $duration,
                'down_payment' => $downPayment,
                'repayment_duration' => $duration,
                'total_amount' => $totalAmount,
                'message' => 'Counter offer accepted. Please pay your initial down payment to complete the order.',
            ], 'Counter offer accepted successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('Counteroffer Accept Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to accept counter offer: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/bnpl/applications/{id}/confirm-down-payment
     * After Flutterwave success: create BNPL order and installments, complete application flow.
     */
    public function confirmDownPayment(Request $request, $id)
    {
        try {
            $request->validate([
                'transaction_reference' => 'nullable|max:255', // accept string or number (Flutterwave may return numeric id)
                'amount_paid' => 'nullable|numeric|min:0',
                'delivery_address_id' => 'nullable|exists:delivery_addresses,id',
            ]);

            $application = LoanApplication::with('mono.loanCalculation')->where('id', $id)
                ->where('user_id', Auth::id())
                ->first();

            if (!$application) {
                return ResponseHelper::error('Application not found', 404);
            }

            $status = $application->status;
            if (!in_array($status, ['approved', 'counter_offer_accepted'], true)) {
                return ResponseHelper::error('Application must be approved or counter offer accepted before paying down payment', 422);
            }

            $mono = $application->mono;
            if (!$mono) {
                return ResponseHelper::error('Loan calculation not found for this application', 404);
            }

            // Check if order already created for this application (idempotent)
            $existingOrder = \App\Models\Order::where('mono_calculation_id', $mono->id)
                ->where('user_id', Auth::id())
                ->where(function ($q) {
                    $q->where('order_type', 'bnpl')->orWhereNull('order_type');
                })
                ->first();

            if ($existingOrder) {
                return ResponseHelper::success([
                    'order_id' => $existingOrder->id,
                    'order_number' => $existingOrder->order_number,
                    'message' => 'Order already created for this application.',
                ], 'Order already exists');
            }

            $calc = $mono->loanCalculation;
            if ($calc) {
                $duration = (int) $mono->repayment_duration;
                $totalAmount = (float) $mono->total_amount;
                $downPayment = (float) $mono->down_payment;
                $monthlyPayment = $duration > 0 ? round(($totalAmount - $downPayment) / $duration, 2) : 0;
                $calc->repayment_duration = $duration;
                $calc->monthly_payment = $monthlyPayment;
                $calc->repayment_date = $calc->repayment_date ?? now()->addMonth();
                $calc->save();
            }

            $deliveryAddressId = $request->input('delivery_address_id');
            if ($deliveryAddressId) {
                $owned = \App\Models\DeliveryAddress::where('id', $deliveryAddressId)
                    ->where('user_id', Auth::id())
                    ->exists();
                if (!$owned) {
                    $deliveryAddressId = null;
                }
            }

            // If no delivery address provided, create one from the application's property address
            if (!$deliveryAddressId && ($application->property_address || $application->property_state)) {
                $user = Auth::user();
                $deliveryAddress = \App\Models\DeliveryAddress::create([
                    'user_id' => Auth::id(),
                    'address' => $application->property_address ?? '',
                    'state' => $application->property_state ?? null,
                    'title' => 'BNPL delivery',
                    'phone_number' => $user->phone ?? null,
                ]);
                $deliveryAddressId = $deliveryAddress->id;
            }

            $order = Order::create([
                'user_id' => Auth::id(),
                'order_number' => strtoupper('BNPL-' . \Illuminate\Support\Str::random(8)),
                'total_price' => (float) $mono->total_amount,
                'payment_status' => 'paid',
                'order_status' => 'pending',
                'payment_method' => 'flutterwave',
                'mono_calculation_id' => $mono->id,
                'order_type' => 'bnpl',
                'delivery_address_id' => $deliveryAddressId,
            ]);

            // Create order items from application snapshot (so order detail shows all bundles/products)
            $snapshot = $application->order_items_snapshot;
            if (!empty($snapshot) && is_array($snapshot)) {
                foreach ($snapshot as $row) {
                    $itemableType = $row['itemable_type'] ?? null;
                    $itemableId = (int) ($row['itemable_id'] ?? 0);
                    $quantity = (int) ($row['quantity'] ?? 1);
                    $unitPrice = (float) ($row['unit_price'] ?? 0);
                    $subtotal = (float) ($row['subtotal'] ?? $unitPrice * $quantity);
                    if ($itemableType && $itemableId > 0) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'itemable_type' => $itemableType,
                            'itemable_id' => $itemableId,
                            'quantity' => $quantity,
                            'unit_price' => $unitPrice,
                            'subtotal' => $subtotal,
                        ]);
                    }
                }
            }

            \App\Services\LoanInstallmentScheduler::generate($mono->id, null, false);

            // Update application status to 'approved' after down payment is confirmed
            $application->status = 'approved';
            $application->save();

            // Add remaining loan balance to user's loan wallet
            $remainingBalance = (float) $mono->total_amount - (float) $mono->down_payment;
            if ($remainingBalance > 0) {
                $wallet = \App\Models\Wallet::firstOrCreate(
                    ['user_id' => Auth::id()],
                    ['loan_balance' => 0, 'shop_balance' => 0]
                );
                $currentLoanBalance = (float) ($wallet->loan_balance ?? 0);
                $wallet->loan_balance = $currentLoanBalance + $remainingBalance;
                $wallet->save();
            }

            return ResponseHelper::success([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'message' => 'Order completed. Your BNPL order has been placed successfully.',
            ], 'Down payment confirmed, order created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('BNPL Confirm Down Payment Error: ' . $e->getMessage(), [
                'application_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return ResponseHelper::error('Failed to confirm down payment: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/bnpl/orders
     * Get all BNPL orders for the authenticated user
     */
    public function getOrders(Request $request)
    {
        try {
            $userId = Auth::id();
            
            $query = Order::with([
                'items.itemable',
                'deliveryAddress',
                'monoCalculation.loanInstallments',
                'monoCalculation.loanRepayments',
            ])
            ->where('user_id', $userId)
            ->where('order_type', 'bnpl');

            // Filter by status if provided
            if ($request->has('status')) {
                $query->where('order_status', $request->status);
            }

            // Sort by latest first
            $orders = $query->orderBy('created_at', 'desc')
                ->paginate($request->get('per_page', 15));

            $formattedData = $orders->getCollection()->map(function ($order) {
                return $this->formatBnplOrder($order);
            });

            return ResponseHelper::success([
                'data' => $formattedData,
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                    'from' => $orders->firstItem(),
                    'to' => $orders->lastItem(),
                ],
            ], 'BNPL orders retrieved successfully');

        } catch (Exception $e) {
            Log::error('BNPL Orders List Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to retrieve BNPL orders: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/bnpl/orders/{order_id}
     * Get single BNPL order with full repayment details
     */
    public function getOrderDetails($orderId)
    {
        try {
            $userId = Auth::id();
            
            $order = Order::with([
                'items.itemable',
                'deliveryAddress',
                'monoCalculation.loanInstallments.transaction',
                'monoCalculation.loanRepayments',
                'monoCalculation.loanCalculation',
            ])
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('order_type', 'bnpl')
            ->first();

            if (!$order) {
                return ResponseHelper::error('BNPL order not found', 404);
            }

            // Get loan application linked to this order
            $loanApplication = null;
            if ($order->mono_calculation_id) {
                $loanApplication = LoanApplication::with(['guarantor'])
                    ->where('mono_loan_calculation', $order->mono_calculation_id)
                    ->where('user_id', $userId)
                    ->first();
            }

            // Format installments with repayment schedule
            $installments = [];
            $repaymentSchedule = [];
            if ($order->monoCalculation) {
                $allInstallments = $order->monoCalculation->loanInstallments()
                    ->orderBy('payment_date', 'asc')
                    ->get();
                
                foreach ($allInstallments as $installment) {
                    $installmentData = [
                        'id' => $installment->id,
                        'installment_number' => $installment->installment_number ?? null,
                        'amount' => (float) $installment->amount,
                        'payment_date' => $installment->payment_date ? $installment->payment_date->format('Y-m-d') : null,
                        'status' => $installment->status,
                        'paid_at' => $installment->paid_at ? $installment->paid_at->format('Y-m-d H:i:s') : null,
                        'is_overdue' => $installment->payment_date && $installment->payment_date->lt(now()) && $installment->status !== 'paid',
                        'transaction' => $installment->transaction ? [
                            'id' => $installment->transaction->id,
                            'tx_id' => $installment->transaction->tx_id,
                            'method' => $installment->transaction->method,
                            'amount' => (float) $installment->transaction->amount,
                            'transacted_at' => $installment->transaction->transacted_at ? $installment->transaction->transacted_at->format('Y-m-d H:i:s') : null,
                        ] : null,
                    ];
                    $installments[] = $installmentData;
                    $repaymentSchedule[] = $installmentData;
                }
            }

            // Get repayment history
            $repayments = [];
            if ($order->monoCalculation) {
                $repaymentRecords = $order->monoCalculation->loanRepayments()
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                foreach ($repaymentRecords as $repayment) {
                    $repayments[] = [
                        'id' => $repayment->id,
                        'amount' => (float) $repayment->amount,
                        'status' => $repayment->status,
                        'created_at' => $repayment->created_at->format('Y-m-d H:i:s'),
                    ];
                }
            }

            // Calculate summary
            $totalInstallments = count($installments);
            $paidInstallments = count(array_filter($installments, fn($i) => $i['status'] === 'paid'));
            $pendingInstallments = count(array_filter($installments, fn($i) => $i['status'] !== 'paid'));
            $overdueInstallments = count(array_filter($installments, fn($i) => $i['is_overdue'] === true));
            $totalAmount = array_sum(array_column($installments, 'amount'));
            $paidAmount = array_sum(array_column(array_filter($installments, fn($i) => $i['status'] === 'paid'), 'amount'));
            $pendingAmount = $totalAmount - $paidAmount;

            $orderData = $this->formatBnplOrder($order);
            // For BNPL orders without a linked delivery address, use application's property address
            if (!$orderData['delivery_address'] && $loanApplication) {
                $user = Auth::user();
                $orderData['delivery_address'] = (object) [
                    'address' => $loanApplication->property_address ?? '',
                    'state' => $loanApplication->property_state ?? null,
                    'title' => 'BNPL delivery',
                    'phone_number' => $user ? $user->phone : null,
                ];
            }
            $orderData['loan_application'] = $loanApplication ? [
                'id' => $loanApplication->id,
                'status' => $loanApplication->status,
                'loan_amount' => (float) $loanApplication->loan_amount,
                'repayment_duration' => $loanApplication->repayment_duration,
                'property_address' => $loanApplication->property_address,
                'property_state' => $loanApplication->property_state,
                'guarantor' => $loanApplication->guarantor ? [
                    'id' => $loanApplication->guarantor->id,
                    'full_name' => $loanApplication->guarantor->full_name,
                    'status' => $loanApplication->guarantor->status,
                    'signed_form_path' => $loanApplication->guarantor->signed_form_path,
                    'has_signed_form' => !empty($loanApplication->guarantor->signed_form_path),
                ] : null,
            ] : null;
            $orderData['repayment_schedule'] = $repaymentSchedule;
            $orderData['repayment_summary'] = [
                'total_installments' => $totalInstallments,
                'paid_installments' => $paidInstallments,
                'pending_installments' => $pendingInstallments,
                'overdue_installments' => $overdueInstallments,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'pending_amount' => $pendingAmount,
            ];
            $orderData['repayment_history'] = $repayments;
            $orderData['loan_details'] = $order->monoCalculation ? [
                'loan_amount' => (float) ($order->monoCalculation->loan_amount ?? 0),
                'down_payment' => (float) ($order->monoCalculation->down_payment ?? 0),
                'total_amount' => (float) ($order->monoCalculation->total_amount ?? 0),
                'repayment_duration' => $order->monoCalculation->repayment_duration,
                'interest_rate' => $order->monoCalculation->interest_rate,
            ] : null;

            return ResponseHelper::success($orderData, 'BNPL order details retrieved successfully');

        } catch (Exception $e) {
            Log::error('BNPL Order Details Error: ' . $e->getMessage(), [
                'order_id' => $orderId,
                'trace' => $e->getTraceAsString()
            ]);
            return ResponseHelper::error('Failed to retrieve BNPL order details: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/bnpl/applications/{application_id}/repayment-schedule
     * Get repayment schedule for a specific BNPL application
     */
    public function getRepaymentSchedule($applicationId)
    {
        try {
            $userId = Auth::id();
            
            $application = LoanApplication::with(['mono.loanInstallments.transaction'])
                ->where('id', $applicationId)
                ->where('user_id', $userId)
                ->first();

            if (!$application) {
                return ResponseHelper::error('Application not found', 404);
            }

            if (!$application->mono_loan_calculation || !$application->mono) {
                return ResponseHelper::error('Loan calculation not found for this application', 404);
            }

            $installments = $application->mono->loanInstallments()
                ->orderBy('payment_date', 'asc')
                ->get();

            $schedule = [];
            foreach ($installments as $installment) {
                $schedule[] = [
                    'id' => $installment->id,
                    'installment_number' => $installment->installment_number ?? null,
                    'amount' => (float) $installment->amount,
                    'payment_date' => $installment->payment_date ? $installment->payment_date->format('Y-m-d') : null,
                    'status' => $installment->status,
                    'paid_at' => $installment->paid_at ? $installment->paid_at->format('Y-m-d H:i:s') : null,
                    'is_overdue' => $installment->payment_date && $installment->payment_date->lt(now()) && $installment->status !== 'paid',
                    'days_until_due' => $installment->payment_date ? now()->diffInDays($installment->payment_date, false) : null,
                    'transaction' => $installment->transaction ? [
                        'id' => $installment->transaction->id,
                        'tx_id' => $installment->transaction->tx_id,
                        'method' => $installment->transaction->method,
                        'amount' => (float) $installment->transaction->amount,
                        'transacted_at' => $installment->transaction->transacted_at ? $installment->transaction->transacted_at->format('Y-m-d H:i:s') : null,
                    ] : null,
                ];
            }

            // Calculate summary
            $totalInstallments = count($schedule);
            $paidInstallments = count(array_filter($schedule, fn($i) => $i['status'] === 'paid'));
            $pendingInstallments = count(array_filter($schedule, fn($i) => $i['status'] !== 'paid'));
            $overdueInstallments = count(array_filter($schedule, fn($i) => $i['is_overdue'] === true));
            $totalAmount = array_sum(array_column($schedule, 'amount'));
            $paidAmount = array_sum(array_column(array_filter($schedule, fn($i) => $i['status'] === 'paid'), 'amount'));
            $pendingAmount = $totalAmount - $paidAmount;

            return ResponseHelper::success([
                'application_id' => $application->id,
                'loan_amount' => (float) ($application->mono->loan_amount ?? $application->loan_amount),
                'repayment_duration' => $application->mono->repayment_duration ?? $application->repayment_duration,
                'schedule' => $schedule,
                'summary' => [
                    'total_installments' => $totalInstallments,
                    'paid_installments' => $paidInstallments,
                    'pending_installments' => $pendingInstallments,
                    'overdue_installments' => $overdueInstallments,
                    'total_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'pending_amount' => $pendingAmount,
                ],
            ], 'Repayment schedule retrieved successfully');

        } catch (Exception $e) {
            Log::error('Repayment Schedule Error: ' . $e->getMessage(), [
                'application_id' => $applicationId,
                'trace' => $e->getTraceAsString()
            ]);
            return ResponseHelper::error('Failed to retrieve repayment schedule: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper method to format BNPL order
     */
    private function formatBnplOrder($order)
    {
        $installmentsCount = 0;
        $paidInstallmentsCount = 0;
        $nextPaymentDate = null;
        $nextPaymentAmount = null;

        if ($order->monoCalculation) {
            $allInstallments = $order->monoCalculation->loanInstallments;
            $installmentsCount = $allInstallments->count();
            $paidInstallmentsCount = $allInstallments->where('status', 'paid')->count();
            
            $nextInstallment = $allInstallments->where('status', '!=', 'paid')
                ->sortBy('payment_date')
                ->first();
            
            if ($nextInstallment) {
                $nextPaymentDate = $nextInstallment->payment_date ? $nextInstallment->payment_date->format('Y-m-d') : null;
                $nextPaymentAmount = (float) $nextInstallment->amount;
            }
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'order_status' => $order->order_status,
            'payment_status' => $order->payment_status,
            'total_price' => (float) $order->total_price,
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'itemable_type' => strtolower(class_basename($item->itemable_type)),
                    'itemable_id' => $item->itemable_id,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'subtotal' => (float) $item->subtotal,
                    'item' => $item->itemable ? [
                        'id' => $item->itemable->id,
                        'title' => $item->itemable->title ?? null,
                    ] : null,
                ];
            }),
            'delivery_address' => $order->deliveryAddress,
            'loan_summary' => [
                'total_installments' => $installmentsCount,
                'paid_installments' => $paidInstallmentsCount,
                'pending_installments' => $installmentsCount - $paidInstallmentsCount,
                'next_payment_date' => $nextPaymentDate,
                'next_payment_amount' => $nextPaymentAmount,
            ],
            'created_at' => $order->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $order->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
