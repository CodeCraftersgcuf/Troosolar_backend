<?php

namespace App\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\ReferralSettings;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ReferralAdminController extends Controller
{
    /**
     * Get referral settings
     * GET /api/admin/referral/settings
     */
    public function getSettings()
    {
        try {
            $settings = ReferralSettings::getSettings();
            
            return ResponseHelper::success([
                'commission_percentage' => (float) $settings->commission_percentage,
                'minimum_withdrawal' => (float) $settings->minimum_withdrawal,
            ], 'Referral settings retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve referral settings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update referral settings
     * PUT /api/admin/referral/settings
     */
    public function updateSettings(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'commission_percentage' => 'nullable|numeric|min:0|max:100',
                'minimum_withdrawal' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $data = [];
            if ($request->has('commission_percentage')) {
                $data['commission_percentage'] = $request->commission_percentage;
            }
            if ($request->has('minimum_withdrawal')) {
                $data['minimum_withdrawal'] = $request->minimum_withdrawal;
            }

            if (empty($data)) {
                return ResponseHelper::error('No data provided to update', 400);
            }

            $settings = ReferralSettings::updateSettings($data);

            return ResponseHelper::success([
                'commission_percentage' => (float) $settings->commission_percentage,
                'minimum_withdrawal' => (float) $settings->minimum_withdrawal,
            ], 'Referral settings updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to update referral settings: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get referral list with user statistics
     * GET /api/admin/referral/list
     */
    public function getReferralList(Request $request)
    {
        try {
            $query = User::with('wallet')
                ->whereNotNull('user_code')
                ->select([
                    'users.id',
                    'users.first_name',
                    'users.sur_name',
                    'users.email',
                    'users.user_code',
                    'users.created_at',
                    DB::raw('(SELECT COUNT(*) FROM users AS referred_users WHERE referred_users.refferal_code = users.user_code) as referral_count'),
                    DB::raw('COALESCE(wallets.referral_balance, 0) as total_earned')
                ])
                ->leftJoin('wallets', 'wallets.user_id', '=', 'users.id');

            // Search functionality
            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                      ->orWhere('sur_name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('user_code', 'like', "%{$search}%");
                });
            }

            // Sort functionality
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            $allowedSorts = ['name', 'referral_count', 'total_earned', 'created_at'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'created_at';
            }

            if ($sortBy === 'name') {
                $query->orderBy('first_name', $sortOrder);
            } elseif ($sortBy === 'referral_count') {
                $query->orderBy('referral_count', $sortOrder);
            } elseif ($sortBy === 'total_earned') {
                $query->orderBy('total_earned', $sortOrder);
            } else {
                $query->orderBy('created_at', $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $referrals = $query->paginate($perPage);

            // Format response
            $formattedData = $referrals->getCollection()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->sur_name ?? '')),
                    'email' => $user->email,
                    'user_code' => $user->user_code,
                    'no_of_referral' => (int) $user->referral_count,
                    'amount_earned' => number_format((float) $user->total_earned, 2),
                    'date_joined' => $user->created_at ? $user->created_at->format('d-m-y/h:iA') : null,
                ];
            });

            return ResponseHelper::success([
                'data' => $formattedData,
                'pagination' => [
                    'current_page' => $referrals->currentPage(),
                    'last_page' => $referrals->lastPage(),
                    'per_page' => $referrals->perPage(),
                    'total' => $referrals->total(),
                ],
            ], 'Referral list retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve referral list: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get single user referral details
     * GET /api/admin/referral/user/{userId}
     */
    public function getUserReferralDetails($userId)
    {
        try {
            $user = User::with('wallet')->findOrFail($userId);

            // Get referred users
            $referredUsers = User::where('refferal_code', $user->user_code)
                ->select('id', 'first_name', 'sur_name', 'email', 'created_at')
                ->get();

            // Calculate total earned from referred users' wallets
            $totalEarned = Wallet::whereIn('user_id', $referredUsers->pluck('id'))
                ->sum('referral_balance');

            // Get user's own referral balance
            $userReferralBalance = $user->wallet ? $user->wallet->referral_balance : 0;

            return ResponseHelper::success([
                'user' => [
                    'id' => $user->id,
                    'name' => trim(($user->first_name ?? '') . ' ' . ($user->sur_name ?? '')),
                    'email' => $user->email,
                    'user_code' => $user->user_code,
                    'referral_code_used' => $user->refferal_code,
                    'referral_balance' => number_format((float) $userReferralBalance, 2),
                ],
                'statistics' => [
                    'total_referrals' => $referredUsers->count(),
                    'total_earned_from_referrals' => number_format((float) $totalEarned, 2),
                    'date_joined' => $user->created_at ? $user->created_at->format('d-m-y/h:iA') : null,
                ],
                'referred_users' => $referredUsers->map(function ($referred) {
                    return [
                        'id' => $referred->id,
                        'name' => trim(($referred->first_name ?? '') . ' ' . ($referred->sur_name ?? '')),
                        'email' => $referred->email,
                        'joined_at' => $referred->created_at ? $referred->created_at->format('d-m-y/h:iA') : null,
                    ];
                }),
            ], 'User referral details retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve user referral details: ' . $e->getMessage(), 500);
        }
    }
}

