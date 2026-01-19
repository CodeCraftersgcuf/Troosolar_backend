<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Mail\OtpEmail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailServiceController extends Controller
{
    /**
     * Send OTP Email
     * POST /api/email/send-otp
     * 
     * This is a service endpoint that can be called from other applications
     * to send OTP emails using the configured SMTP settings.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOtp(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
                'otp_code' => 'required|string|size:4|regex:/^[0-9]{4}$/',
                'subject' => 'nullable|string|max:255',
                'message' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->input('email');
            $otpCode = $request->input('otp_code');
            $subject = $request->input('subject');
            $customMessage = $request->input('message');

            // Send email
            Mail::to($email)->send(new OtpEmail($otpCode, $subject, $customMessage));

            return ResponseHelper::success([
                'email' => $email,
                'sent_at' => now()->toIso8601String(),
            ], 'OTP email sent successfully.');

        } catch (Exception $e) {
            Log::error('Email Service Error: ' . $e->getMessage(), [
                'email' => $request->input('email'),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ResponseHelper::error('Failed to send OTP email: ' . $e->getMessage(), 500);
        }
    }
}
