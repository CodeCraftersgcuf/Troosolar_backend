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
            
            $fixedNgn = (float) ($settings->referral_fixed_ngn ?? 0);
            if ($fixedNgn <= 0) {
                $fixedNgn = (float) ($settings->referral_reward_value ?? 0);
            }

            return ResponseHelper::success([
                'commission_percentage' => (float) $settings->commission_percentage,
                'referral_percentage' => (float) $settings->commission_percentage,
                'minimum_withdrawal' => (float) $settings->minimum_withdrawal,
                'outright_discount_percentage' => (float) ($settings->outright_discount_percentage ?? 0),
                'referral_reward_type' => (string) ($settings->referral_reward_type ?? 'fixed'),
                'referral_reward_value' => (float) ($settings->referral_reward_value ?? 0),
                'referral_fixed_ngn' => $fixedNgn > 0 ? $fixedNgn : 50000.0,
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
                'referral_percentage' => 'nullable|numeric|min:0|max:100',
                'referral_fixed_ngn' => 'nullable|numeric|min:0',
                'minimum_withdrawal' => 'nullable|numeric|min:0',
                'outright_discount_percentage' => 'nullable|numeric|min:0|max:100',
                'referral_reward_type' => 'nullable|in:percentage,fixed',
                'referral_reward_value' => 'nullable|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return ResponseHelper::error('Validation failed', 422, $validator->errors());
            }

            $data = [];
            if ($request->has('commission_percentage')) {
                $data['commission_percentage'] = $request->commission_percentage;
            }
            if ($request->has('referral_percentage')) {
                $data['commission_percentage'] = $request->referral_percentage;
            }
            if ($request->has('minimum_withdrawal')) {
                $data['minimum_withdrawal'] = $request->minimum_withdrawal;
            }
            if ($request->has('outright_discount_percentage')) {
                $data['outright_discount_percentage'] = $request->outright_discount_percentage;
            }
            if ($request->has('referral_reward_type')) {
                $data['referral_reward_type'] = $request->referral_reward_type;
            }
            if ($request->has('referral_reward_value')) {
                $data['referral_reward_value'] = $request->referral_reward_value;
            }
            if ($request->has('referral_fixed_ngn')) {
                $data['referral_fixed_ngn'] = $request->referral_fixed_ngn;
            }

            // Backward compatibility: legacy UI sends commission_percentage only (no explicit reward fields).
            if (
                $request->has('commission_percentage')
                && ! $request->has('referral_reward_value')
                && ! $request->has('referral_reward_type')
                && ! $request->has('referral_fixed_ngn')
                && ! $request->has('referral_percentage')
            ) {
                $data['referral_reward_type'] = 'percentage';
                $data['referral_reward_value'] = $request->commission_percentage;
            }
            // Keep legacy field in sync where reward type is percentage.
            if (($data['referral_reward_type'] ?? null) === 'percentage' && array_key_exists('referral_reward_value', $data)) {
                $data['commission_percentage'] = $data['referral_reward_value'];
            }

            if (empty($data)) {
                return ResponseHelper::error('No data provided to update', 400);
            }

            $referralTouched = $request->hasAny([
                'referral_reward_type',
                'referral_fixed_ngn',
                'referral_percentage',
                'commission_percentage',
                'referral_reward_value',
            ]);

            if ($referralTouched) {
                $data = $this->mergeReferralRewardSnapshot($data, ReferralSettings::getSettings());
            }

            $settings = ReferralSettings::updateSettings($data);

            $fixedNgn = (float) ($settings->referral_fixed_ngn ?? 0);
            if ($fixedNgn <= 0) {
                $fixedNgn = (float) ($settings->referral_reward_value ?? 0);
            }

            return ResponseHelper::success([
                'commission_percentage' => (float) $settings->commission_percentage,
                'referral_percentage' => (float) $settings->commission_percentage,
                'minimum_withdrawal' => (float) $settings->minimum_withdrawal,
                'outright_discount_percentage' => (float) ($settings->outright_discount_percentage ?? 0),
                'referral_reward_type' => (string) ($settings->referral_reward_type ?? 'fixed'),
                'referral_reward_value' => (float) ($settings->referral_reward_value ?? 0),
                'referral_fixed_ngn' => $fixedNgn > 0 ? $fixedNgn : 50000.0,
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
     * Users who registered with another user's referral code (for admin visibility).
     * GET /api/admin/referral/referred-signups
     */
    public function getReferredSignups(Request $request)
    {
        try {
            $query = User::query()
                ->whereNotNull('refferal_code')
                ->where('refferal_code', '!=', '')
                ->with([
                    'referrer' => function ($q) {
                        $q->select('id', 'first_name', 'sur_name', 'email', 'user_code');
                    },
                ])
                ->orderByDesc('created_at');

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('sur_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('refferal_code', 'like', "%{$search}%");
                });
            }

            $perPage = min(max((int) $request->get('per_page', 15), 1), 100);
            $paginated = $query->paginate($perPage);

            $formattedData = $paginated->getCollection()->map(function ($u) {
                $ref = $u->referrer;

                return [
                    'id' => $u->id,
                    'name' => trim(($u->first_name ?? '').' '.($u->sur_name ?? '')),
                    'email' => $u->email,
                    'code_used' => $u->refferal_code,
                    'referrer_name' => $ref ? trim(($ref->first_name ?? '').' '.($ref->sur_name ?? '')) : null,
                    'referrer_email' => $ref->email ?? null,
                    'referrer_user_code' => $ref->user_code ?? null,
                    'joined_at' => $u->created_at ? $u->created_at->format('d-m-y/h:iA') : null,
                ];
            });

            return ResponseHelper::success([
                'data' => $formattedData,
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ],
            ], 'Referred signups retrieved successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error('Failed to retrieve referred signups: '.$e->getMessage(), 500);
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

    /**
     * Keep referral_fixed_ngn, commission %, referral_reward_type and referral_reward_value aligned.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mergeReferralRewardSnapshot(array $data, ReferralSettings $current): array
    {
        $type = strtolower((string) ($data['referral_reward_type'] ?? $current->referral_reward_type ?? 'fixed'));

        // Fixed ₦: only overwrite when the request included it; otherwise keep DB value.
        if (array_key_exists('referral_fixed_ngn', $data)) {
            $fixed = max(0, (float) $data['referral_fixed_ngn']);
        } else {
            $fixed = (float) ($current->referral_fixed_ngn ?? 0);
            if ($fixed <= 0) {
                $fb = (float) ($current->referral_reward_value ?? 0);
                if (strtolower((string) $current->referral_reward_type) === 'fixed' && $fb > 0) {
                    $fixed = $fb;
                }
            }
        }

        // Percentage: only overwrite when commission_percentage was set (from referral_percentage input).
        if (array_key_exists('commission_percentage', $data)) {
            $pct = (float) $data['commission_percentage'];
        } else {
            $pct = (float) ($current->commission_percentage ?? 0);
            if ($pct <= 0) {
                $fb = (float) ($current->referral_reward_value ?? 0);
                if (strtolower((string) $current->referral_reward_type) === 'percentage' && $fb > 0) {
                    $pct = $fb;
                }
            }
        }
        if ($pct > 100) {
            $pct = 100.0;
        }
        if ($pct < 0) {
            $pct = 0.0;
        }

        // Ensure the *active* rule has a usable value (first-time / edge cases).
        if ($type === 'fixed' && $fixed <= 0) {
            $fixed = 50000.0;
        }
        if ($type === 'percentage' && $pct <= 0) {
            $pct = 5.0;
        }

        $data['referral_reward_type'] = $type;
        $data['referral_fixed_ngn'] = round($fixed, 2);
        $data['commission_percentage'] = round($pct, 2);
        $data['referral_reward_value'] = $type === 'fixed' ? $data['referral_fixed_ngn'] : $data['commission_percentage'];

        return $data;
    }
}

