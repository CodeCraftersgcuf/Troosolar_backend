<?php

namespace App\Http\Controllers\Api\Website;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    /**
     * GET /api/calendar/slots
     * Get available calendar slots for audit or installation
     * 
     * Query Params:
     * - type: 'audit' or 'installation'
     * - payment_date: YYYY-MM-DD (date when payment was confirmed)
     */
    public function getSlots(Request $request)
    {
        try {
            $type = $request->query('type'); // 'audit' or 'installation'
            $paymentDate = $request->query('payment_date'); // YYYY-MM-DD

            if (!$type || !in_array($type, ['audit', 'installation'])) {
                return ResponseHelper::error('Invalid type. Must be "audit" or "installation"', 422);
            }

            if (!$paymentDate) {
                return ResponseHelper::error('payment_date is required', 422);
            }

            try {
                $paymentDateCarbon = Carbon::parse($paymentDate);
            } catch (Exception $e) {
                return ResponseHelper::error('Invalid payment_date format. Use YYYY-MM-DD', 422);
            }

            // Calculate start date based on type
            // Audit: 48 hours after payment
            // Installation: 72 hours after payment
            $hoursOffset = $type === 'audit' ? 48 : 72;
            $startDate = $paymentDateCarbon->copy()->addHours($hoursOffset);

            // Generate available slots for the next 30 days from start date
            $slots = [];
            $currentDate = $startDate->copy();

            // Available time slots (9 AM to 5 PM, hourly)
            $timeSlots = [];
            for ($hour = 9; $hour <= 17; $hour++) {
                $timeSlots[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
            }

            // Generate slots for next 30 days
            for ($day = 0; $day < 30; $day++) {
                $date = $currentDate->copy()->addDays($day);
                
                // Skip weekends (optional - you can remove this if you work weekends)
                if ($date->isWeekend()) {
                    continue;
                }

                foreach ($timeSlots as $time) {
                    $slots[] = [
                        'date' => $date->format('Y-m-d'),
                        'time' => $time,
                        'datetime' => $date->format('Y-m-d') . ' ' . $time . ':00',
                        'available' => true, // In real app, check against existing bookings
                    ];
                }
            }

            return ResponseHelper::success([
                'type' => $type,
                'payment_date' => $paymentDate,
                'start_date' => $startDate->format('Y-m-d H:i:s'),
                'slots' => $slots,
                'message' => $type === 'audit' 
                    ? 'Available slots starting 48 hours after payment confirmation'
                    : 'Available slots starting 72 hours after payment confirmation'
            ], 'Available slots retrieved successfully');

        } catch (Exception $e) {
            Log::error('Calendar Slots Error: ' . $e->getMessage());
            return ResponseHelper::error('Failed to retrieve calendar slots: ' . $e->getMessage(), 500);
        }
    }
}
