<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('referral_settings', 'outright_discount_percentage')) {
                $table->decimal('outright_discount_percentage', 5, 2)
                    ->default(0.00)
                    ->after('minimum_withdrawal');
            }
            if (!Schema::hasColumn('referral_settings', 'referral_reward_type')) {
                $table->string('referral_reward_type', 20)
                    ->default('percentage')
                    ->after('outright_discount_percentage');
            }
            if (!Schema::hasColumn('referral_settings', 'referral_reward_value')) {
                $table->decimal('referral_reward_value', 10, 2)
                    ->default(0.00)
                    ->after('referral_reward_type');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'referral_reward_processed_at')) {
                $table->timestamp('referral_reward_processed_at')
                    ->nullable()
                    ->after('payment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'referral_reward_processed_at')) {
                $table->dropColumn('referral_reward_processed_at');
            }
        });

        Schema::table('referral_settings', function (Blueprint $table) {
            if (Schema::hasColumn('referral_settings', 'referral_reward_value')) {
                $table->dropColumn('referral_reward_value');
            }
            if (Schema::hasColumn('referral_settings', 'referral_reward_type')) {
                $table->dropColumn('referral_reward_type');
            }
            if (Schema::hasColumn('referral_settings', 'outright_discount_percentage')) {
                $table->dropColumn('outright_discount_percentage');
            }
        });
    }
};

