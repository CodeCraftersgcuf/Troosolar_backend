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
        Schema::table('bnpl_settings', function (Blueprint $table) {
            $table->decimal('credit_check_fee', 12, 2)->default(1000)->after('minimum_loan_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bnpl_settings', function (Blueprint $table) {
            $table->dropColumn('credit_check_fee');
        });
    }
};

