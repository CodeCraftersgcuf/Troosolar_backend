<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Mail\BNPLStatusEmail;
use App\Models\Guarantor;
use App\Models\LoanApplication;
use App\Models\MonoLoanCalculation;
use App\Models\LoanCalculation;
use App\Models\Notification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
                ->where(function ($q) {
                    // BNPL applications: have customer_type and/or product_category set
                    $q->whereNotNull('customer_type')
                      ->orWhereNotNull('product_category');
                });

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
     * Update BNPL application (assign beneficiary email, name, phone – like loan flow)
     * PUT /api/admin/bnpl/applications/{id}
     */
    public function updateApplication(Request $request, $id)
    {
        try {
            $request->validate([
                'beneficiary_email' => 'nullable|email|max:255',
                'beneficiary_name' => 'nullable|string|max:255',
                'beneficiary_phone' => 'nullable|string|max:20',
                'beneficiary_relationship' => 'nullable|string|max:100',
            ]);

            $application = LoanApplication::find($id);
            if (!$application) {
                return ResponseHelper::error('BNPL application not found', 404);
            }

            if ($request->filled('beneficiary_email')) {
                $application->beneficiary_email = $request->beneficiary_email;
            }
            if ($request->filled('beneficiary_name')) {
                $application->beneficiary_name = $request->beneficiary_name;
            }
            if ($request->filled('beneficiary_phone')) {
                $application->beneficiary_phone = $request->beneficiary_phone;
            }
            if ($request->filled('beneficiary_relationship')) {
                $application->beneficiary_relationship = $request->beneficiary_relationship;
            }
            $application->save();

            return ResponseHelper::success($application->fresh(['user', 'guarantor', 'mono']), 'BNPL application updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('BNPL Admin Update Application Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to update BNPL application', 500);
        }
    }

    /**
     * Update loan offer (amount, down payment, tenor) for BNPL application
     * PUT /api/admin/bnpl/applications/{id}/offer
     */
    public function updateLoanOffer(Request $request, $id)
    {
        try {
            $request->validate([
                'loan_amount' => 'nullable|numeric|min:0',
                'down_payment' => 'nullable|numeric|min:0',
                'repayment_duration' => 'nullable|integer|in:3,6,9,12',
            ]);

            $application = LoanApplication::with('mono.loanCalculation')->find($id);
            if (!$application) {
                return ResponseHelper::error('BNPL application not found', 404);
            }

            $mono = $application->mono;
            if (!$mono) {
                return ResponseHelper::error('No loan calculation linked to this application', 404);
            }

            $loanAmount = $request->filled('loan_amount') ? (float) $request->loan_amount : (float) $mono->loan_amount;
            $downPayment = $request->filled('down_payment') ? (float) $request->down_payment : (float) $mono->down_payment;
            $duration = $request->filled('repayment_duration') ? (int) $request->repayment_duration : (int) $mono->repayment_duration;

            $interestRate = (float) ($mono->interest_rate ?? 0);
            $totalAmount = $loanAmount + ($loanAmount * $interestRate);
            $monthlyPayment = $duration > 0 ? round(($totalAmount - $downPayment) / $duration, 2) : 0;

            $mono->loan_amount = $loanAmount;
            $mono->down_payment = $downPayment;
            $mono->repayment_duration = $duration;
            $mono->total_amount = $totalAmount;
            $mono->save();

            $application->loan_amount = $loanAmount;
            $application->repayment_duration = $duration;
            $application->save();

            if ($mono->loanCalculation) {
                $mono->loanCalculation->loan_amount = $loanAmount;
                $mono->loanCalculation->repayment_duration = $duration;
                $mono->loanCalculation->monthly_payment = $monthlyPayment;
                $mono->loanCalculation->repayment_date = $mono->loanCalculation->repayment_date ?? now()->addMonth();
                $mono->loanCalculation->save();
            }

            return ResponseHelper::success($application->fresh(['user', 'mono', 'mono.loanCalculation']), 'Loan offer updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error('BNPL Admin Update Offer Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to update loan offer', 500);
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

            $application = LoanApplication::with(['user', 'mono'])->find($id);
            if (!$application) {
                return ResponseHelper::error('BNPL application not found', 404);
            }

            $application->status = $request->status;
            if ($request->has('admin_notes')) {
                $application->admin_notes = $request->admin_notes;
            }
            $application->save();

            if ($request->status === 'counter_offer') {
                $application->counter_offer_min_deposit = $request->counter_offer_min_deposit;
                $application->counter_offer_min_tenor = $request->counter_offer_min_tenor;
                $application->save();
            }

            // When approving, sync counter offer terms to mono if they exist
            if ($request->status === 'approved' && $application->mono) {
                $mono = $application->mono;
                $hasCounterOfferTerms = $application->counter_offer_min_deposit !== null && 
                                       $application->counter_offer_min_tenor !== null;
                
                if ($hasCounterOfferTerms) {
                    // Use counter offer terms
                    $downPayment = (float) $application->counter_offer_min_deposit;
                    $duration = (int) $application->counter_offer_min_tenor;
                    $loanAmount = (float) $mono->loan_amount;
                    $interestRate = (float) ($mono->interest_rate ?? 0);
                    $totalAmount = $loanAmount + ($loanAmount * $interestRate);
                    $monthlyPayment = $duration > 0 ? round(($totalAmount - $downPayment) / $duration, 2) : 0;

                    // Update mono with counter offer terms
                    $mono->down_payment = $downPayment;
                    $mono->repayment_duration = $duration;
                    $mono->total_amount = $totalAmount;
                    $mono->save();

                    // Update application repayment duration
                    $application->repayment_duration = $duration;
                    $application->save();

                    // Update loan calculation if exists
                    if ($mono->loanCalculation) {
                        $mono->loanCalculation->repayment_duration = $duration;
                        $mono->loanCalculation->monthly_payment = $monthlyPayment;
                        $mono->loanCalculation->repayment_date = $mono->loanCalculation->repayment_date ?? now()->addMonth();
                        $mono->loanCalculation->save();
                    }
                }
            }

            // Notify user when admin sends offer or approves/rejects (in-app + email)
            $userId = $application->user_id;
            $status = $request->status;
            if ($userId && in_array($status, ['approved', 'counter_offer', 'rejected'])) {
                $message = $status === 'approved'
                    ? 'Your BNPL application has been approved. Please pay your initial down payment to complete the order.'
                    : ($status === 'counter_offer'
                        ? 'You have a counter offer on your BNPL application. Please review and accept or decline.'
                        : 'We cannot process your BNPL application at this time. Thank you for choosing Troosolar.');
                Notification::create([
                    'user_id' => $userId,
                    'message' => $message,
                    'type' => 'bnpl_status',
                ]);

                // Send email to customer so they know their loan status and can continue
                $user = $application->user;
                if ($user && !empty($user->email)) {
                    try {
                        Mail::to($user->email)->send(new BNPLStatusEmail($user, $application, $status));
                    } catch (\Throwable $e) {
                        Log::warning('BNPL status email failed: ' . $e->getMessage(), [
                            'application_id' => $application->id,
                            'user_id' => $userId,
                            'status' => $status,
                        ]);
                    }
                }
            }

            return ResponseHelper::success($application->fresh(['user', 'guarantor', 'mono']), 'BNPL application status updated successfully');
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

    /**
     * Upload BNPL guarantor form PDF (admin).
     * This file is served when users download the guarantor form after loan approval.
     * POST /api/admin/bnpl/guarantor-form
     */
    public function uploadGuarantorForm(Request $request)
    {
        try {
            $request->validate([
                'guarantor_form' => 'required|file|mimes:pdf|max:10240',
            ], [
                'guarantor_form.required' => 'Please select a PDF file.',
                'guarantor_form.mimes' => 'The file must be a PDF.',
                'guarantor_form.max' => 'The file may not be greater than 10MB.',
            ]);

            $relativePath = config('bnpl.guarantor_form_path', 'documents/guarantor-form.pdf');
            $fullPath = public_path($relativePath);
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $file = $request->file('guarantor_form');
            $file->move($dir, basename($fullPath));

            return ResponseHelper::success([
                'path' => $relativePath,
                'message' => 'Guarantor form updated. Users will download this file when they click Download Guarantor Form.',
            ], 'Guarantor form uploaded successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error('BNPL Admin Upload Guarantor Form Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to upload guarantor form: ' . $e->getMessage(), 500);
        }
    }
}

