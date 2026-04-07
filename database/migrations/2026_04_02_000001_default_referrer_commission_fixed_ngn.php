<?php

use App\Models\ReferralSettings;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * One-time: set default referrer commission to ₦50,000 (fixed) when reward was never configured.
     */
    public function up(): void
    {
        foreach (ReferralSettings::query()->get() as $row) {
            $val = (float) ($row->referral_reward_value ?? 0);
            $type = strtolower((string) ($row->referral_reward_type ?? 'percentage'));
            if ($val <= 0 && $type === 'percentage' && (float) ($row->commission_percentage ?? 0) <= 0) {
                $row->update([
                    'referral_reward_type' => 'fixed',
                    'referral_reward_value' => 50000,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Non-reversible data migration
    }
};
