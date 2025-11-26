<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loan_configurations', function (Blueprint $table) {
            $table->id();
            $table->decimal('insurance_fee_percentage', 5, 2)->default(0.50)->comment('0.5% default');
            $table->decimal('residual_fee_percentage', 5, 2)->default(1.00)->comment('1% default');
            $table->decimal('equity_contribution_min', 5, 2)->default(30.00)->comment('Minimum 30%');
            $table->decimal('equity_contribution_max', 5, 2)->default(80.00)->comment('Maximum 80%');
            $table->decimal('interest_rate_min', 5, 2)->default(3.00)->comment('Minimum 3% monthly');
            $table->decimal('interest_rate_max', 5, 2)->default(4.00)->comment('Maximum 4% monthly');
            $table->integer('repayment_tenor_min')->default(1)->comment('Minimum 1 month');
            $table->integer('repayment_tenor_max')->default(12)->comment('Maximum 12 months');
            $table->decimal('management_fee_percentage', 5, 2)->default(1.00)->comment('1% default');
            $table->decimal('minimum_loan_amount', 10, 2)->default(1500000.00)->comment('₦1,500,000 default');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_configurations');
    }
};
