<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Mail\OtpEmail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class EmailOtpController extends Controller
{
    /**
     * POST /api/email-otp/send
     * Send OTP via email (Public endpoint - can be called from other applications)
     */
    public function sendOtp(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
                'api_password' => 'nullable|string', // Optional password for API authentication
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->email;
            $apiPassword = $request->api_password;

            // Optional: Check API password if you want to secure the endpoint
            // Uncomment below if you want to add API password protection
            // $expectedPassword = env('EMAIL_OTP_API_PASSWORD');
            // if ($expectedPassword && $apiPassword !== $expectedPassword) {
            //     return ResponseHelper::error('Invalid API password', 401);
            // }

            // Generate 4-digit OTP
            $otp = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

            // Store OTP in cache for 10 minutes (600 seconds)
            $cacheKey = 'email_otp_' . md5($email);
            Cache::put($cacheKey, $otp, now()->addMinutes(10));

            // Send email
            try {
                Mail::to($email)->send(new OtpEmail($otp));
            } catch (Exception $mailException) {
                Log::error('Failed to send OTP email: ' . $mailException->getMessage());
                return ResponseHelper::error('Failed to send email. Please check SMTP configuration.', 500);
            }

            // Return success response with OTP (for testing/debugging)
            // In production, you might want to remove the OTP from response
            return ResponseHelper::success([
                'email' => $email,
                'otp' => $otp, // Remove this in production if you don't want to expose OTP
                'expires_in' => 600, // seconds
                'message' => 'OTP sent successfully to email'
            ], 'OTP sent successfully to email');

        } catch (Exception $e) {
            Log::error('Email OTP Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to send OTP', 500);
        }
    }

    /**
     * POST /api/email-otp/verify
     * Verify OTP code
     */
    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|max:255',
                'otp' => 'required|string|size:4',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->email;
            $otp = $request->otp;

            // Get OTP from cache
            $cacheKey = 'email_otp_' . md5($email);
            $storedOtp = Cache::get($cacheKey);

            if (!$storedOtp) {
                return ResponseHelper::error('OTP not found or expired. Please request a new OTP.', 404);
            }

            if ($storedOtp !== $otp) {
                return ResponseHelper::error('Invalid OTP code', 422);
            }

            // OTP is valid - remove it from cache
            Cache::forget($cacheKey);

            return ResponseHelper::success([
                'email' => $email,
                'verified' => true,
                'message' => 'OTP verified successfully'
            ], 'OTP verified successfully');

        } catch (Exception $e) {
            Log::error('Email OTP Verification Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to verify OTP', 500);
        }
    }
}
