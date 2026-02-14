<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Per-application overrides for BNPL: interest and fees (when set, override global bnpl_settings).
     */
    public function up(): void
    {
        Schema::table('mono_loan_calculations', function (Blueprint $table) {
            if (!Schema::hasColumn('mono_loan_calculations', 'management_fee_percentage')) {
                $table->decimal('management_fee_percentage', 5, 2)->nullable()->after('interest_rate');
            }
            if (!Schema::hasColumn('mono_loan_calculations', 'legal_fee_percentage')) {
                $table->decimal('legal_fee_percentage', 5, 2)->nullable()->after('management_fee_percentage');
            }
            if (!Schema::hasColumn('mono_loan_calculations', 'insurance_fee_percentage')) {
                $table->decimal('insurance_fee_percentage', 5, 2)->nullable()->after('legal_fee_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mono_loan_calculations', function (Blueprint $table) {
            $table->dropColumn(['management_fee_percentage', 'legal_fee_percentage', 'insurance_fee_percentage']);
        });
    }
};
