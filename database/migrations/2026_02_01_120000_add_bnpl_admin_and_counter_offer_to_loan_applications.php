<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * BNPL: admin_notes, counter_offer fields for admin send-offer flow.
     */
    public function up(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            if (!Schema::hasColumn('loan_applications', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('guarantor_id');
            }
            if (!Schema::hasColumn('loan_applications', 'counter_offer_min_deposit')) {
                $table->decimal('counter_offer_min_deposit', 14, 2)->nullable()->after('admin_notes');
            }
            if (!Schema::hasColumn('loan_applications', 'counter_offer_min_tenor')) {
                $table->unsignedTinyInteger('counter_offer_min_tenor')->nullable()->comment('3,6,9,12 months')->after('counter_offer_min_deposit');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropColumn(['admin_notes', 'counter_offer_min_deposit', 'counter_offer_min_tenor']);
        });
    }
};
