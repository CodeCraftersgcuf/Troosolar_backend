<?php

use App\Models\ReferralSettings;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('referral_settings', 'referral_fixed_ngn')) {
                $table->decimal('referral_fixed_ngn', 12, 2)
                    ->nullable()
                    ->after('referral_reward_value');
            }
        });

        foreach (ReferralSettings::query()->get() as $row) {
            $type = strtolower((string) ($row->referral_reward_type ?? 'fixed'));
            $val = (float) ($row->referral_reward_value ?? 0);
            $fixedNgn = ($type === 'fixed' && $val > 0) ? $val : 50000.0;
            $comm = (float) ($row->commission_percentage ?? 0);
            if ($comm <= 0) {
                $comm = ($type === 'percentage' && $val > 0) ? $val : 5.0;
            }
            $row->update([
                'referral_fixed_ngn' => $fixedNgn,
                'commission_percentage' => $comm,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            if (Schema::hasColumn('referral_settings', 'referral_fixed_ngn')) {
                $table->dropColumn('referral_fixed_ngn');
            }
        });
    }
};
