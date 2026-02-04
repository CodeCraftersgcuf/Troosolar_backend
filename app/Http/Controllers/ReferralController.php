<?php

namespace App\Http\Controllers;

use App\Helpers\ResponseHelper;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    /**
     * GET /api/get-referral-details
     * Returns the authenticated user's referral code (user_code – the code they share) and balance.
     * If user_code is missing, generates one from the user's name and saves it.
     */
    public function getBalance()
    {
        $user = Auth::user();
        if (!$user) {
            return ResponseHelper::error('Unauthenticated', 401);
        }

        // Ensure user has a referral code (user_code = the code they share with others)
        $referralCode = $user->user_code;
        if (empty($referralCode) || trim($referralCode) === '') {
            $referralCode = $this->generateAndSaveUserCode($user);
        }

        // Ensure user has a wallet (create with 0 balance if missing)
        $wallet = Wallet::where('user_id', $user->id)->first();
        if (!$wallet) {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'status' => 'active',
                'loan_balance' => 0,
                'shop_balance' => 0,
                'referral_balance' => 0,
            ]);
        }

        $referralCount = (int) User::where('refferal_code', $referralCode)->count();

        $data = [
            'referral_code' => $referralCode,
            'referral_balance' => (float) ($wallet->referral_balance ?? 0),
            'my_referrals' => $referralCount,
        ];

        return ResponseHelper::success($data, 'Your Referral Balance');
    }

    /**
     * Generate a unique user_code from the user's name (and id) and save to user.
     */
    private function generateAndSaveUserCode(User $user): string
    {
        $base = trim($user->first_name ?? $user->email ?? 'user');
        $base = preg_replace('/[^a-zA-Z0-9]/', '', $base);
        $base = Str::lower($base ?: 'user');

        $candidate = $base . $user->id;
        if (strlen($candidate) < 4) {
            $candidate = $base . rand(100, 9999);
        }

        // Ensure uniqueness
        $exists = User::where('user_code', $candidate)->where('id', '!=', $user->id)->exists();
        if ($exists) {
            $candidate = $base . $user->id . rand(10, 99);
        }

        $user->user_code = $candidate;
        $user->save();

        return $candidate;
    }
}
