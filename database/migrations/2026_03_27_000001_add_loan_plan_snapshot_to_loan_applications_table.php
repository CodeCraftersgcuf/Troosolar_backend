<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores the exact BNPL "Review Your Loan Plan" breakdown from the dashboard flow
     * so detail pages and APIs match what the customer agreed to.
     */
    public function up(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->json('loan_plan_snapshot')->nullable()->after('order_items_snapshot');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropColumn('loan_plan_snapshot');
        });
    }
};
