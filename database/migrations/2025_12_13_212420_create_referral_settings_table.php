<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('referral_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('commission_percentage', 5, 2)->default(0.00)->comment('Commission percentage for referrals');
            $table->decimal('minimum_withdrawal', 10, 2)->default(0.00)->comment('Minimum withdrawal amount');
            $table->timestamps();
        });

        // Insert default settings
        DB::table('referral_settings')->insert([
            'commission_percentage' => 0.00,
            'minimum_withdrawal' => 0.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_settings');
    }
};
