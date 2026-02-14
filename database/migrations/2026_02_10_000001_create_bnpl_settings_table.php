<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Global BNPL config: interest rate, min down %, fees, minimum loan amount, allowed loan durations.
     */
    public function up(): void
    {
        Schema::create('bnpl_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('interest_rate_percentage', 5, 2)->default(4.00)->comment('Default interest rate %');
            $table->decimal('min_down_percentage', 5, 2)->default(30.00)->comment('Minimum initial deposit % of total');
            $table->decimal('management_fee_percentage', 5, 2)->default(1.00);
            $table->decimal('legal_fee_percentage', 5, 2)->default(0.00);
            $table->decimal('insurance_fee_percentage', 5, 2)->default(0.50);
            $table->decimal('minimum_loan_amount', 12, 2)->default(0)->comment('Minimum loan amount in Naira');
            $table->json('loan_durations')->nullable()->comment('Allowed tenors in months e.g. [3,6,9,12]');
            $table->timestamps();
        });

        // Single row: insert default
        DB::table('bnpl_settings')->insert([
            'interest_rate_percentage' => 4,
            'min_down_percentage' => 30,
            'management_fee_percentage' => 1,
            'legal_fee_percentage' => 0,
            'insurance_fee_percentage' => 0.5,
            'minimum_loan_amount' => 0,
            'loan_durations' => json_encode([3, 6, 9, 12]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bnpl_settings');
    }
};
