<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ReferralSettings;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralRewardService
{
    /**
     * Award referral reward to buyer's referrer.
     * Returns reward amount that was credited (0 if none).
     */
    public function award(User $buyer, float $baseAmount, string $eventLabel, ?Order $order = null): float
    {
        $baseAmount = round(max(0, $baseAmount), 2);
        if ($baseAmount <= 0) {
            return 0.0;
        }

        if ($order && !empty($order->referral_reward_processed_at)) {
            return 0.0;
        }

        $referralCode = trim((string) ($buyer->refferal_code ?? ''));
        if ($referralCode === '') {
            if ($order) {
                $order->referral_reward_processed_at = now();
                $order->save();
            }
            return 0.0;
        }

        $referrer = User::where('user_code', $referralCode)->first();
        if (!$referrer || (int) $referrer->id === (int) $buyer->id) {
            if ($order) {
                $order->referral_reward_processed_at = now();
                $order->save();
            }
            return 0.0;
        }

        $settings = ReferralSettings::getSettings();
        $rewardType = strtolower((string) ($settings->referral_reward_type ?? 'fixed'));

        $fixedNgn = (float) ($settings->referral_fixed_ngn ?? 0);
        if ($fixedNgn <= 0) {
            $fixedNgn = (float) ($settings->referral_reward_value ?? 0);
        }

        $pct = (float) ($settings->commission_percentage ?? 0);
        if ($pct <= 0 && $rewardType === 'percentage') {
            $pct = (float) ($settings->referral_reward_value ?? 0);
        }

        if ($rewardType === 'fixed') {
            $rewardAmount = round(max(0, $fixedNgn), 2);
        } else {
            $rewardAmount = round(($baseAmount * max(0, $pct)) / 100, 2);
        }

        if ($rewardAmount <= 0) {
            if ($order) {
                $order->referral_reward_processed_at = now();
                $order->save();
            }
            return 0.0;
        }

        DB::transaction(function () use ($referrer, $rewardAmount, $buyer, $eventLabel, $order) {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $referrer->id],
                ['loan_balance' => 0, 'shop_balance' => 0, 'referral_balance' => 0, 'status' => 'active']
            );

            $wallet->referral_balance = round(((float) $wallet->referral_balance) + $rewardAmount, 2);
            $wallet->save();

            Transaction::create([
                'user_id' => $referrer->id,
                'amount' => $rewardAmount,
                'tx_id' => now()->format('YmdHis') . rand(1000, 9999),
                'title' => "Referral reward ({$eventLabel})",
                'type' => 'incoming',
                'method' => 'Referral',
                'status' => 'Completed',
                'transacted_at' => now(),
                'reference' => 'referral-reward:' . ($order?->id ?? 'n/a') . ':buyer:' . $buyer->id,
            ]);

            if ($order) {
                $order->referral_reward_processed_at = now();
                $order->save();
            }
        });

        Log::info('Referral reward credited', [
            'buyer_id' => $buyer->id,
            'referrer_id' => $referrer->id,
            'event' => $eventLabel,
            'base_amount' => $baseAmount,
            'reward_amount' => $rewardAmount,
            'order_id' => $order?->id,
        ]);

        return $rewardAmount;
    }
}

