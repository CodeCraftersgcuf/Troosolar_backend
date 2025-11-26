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
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('residual_fee', 10, 2)->default(0.00)->after('insurance_fee')->comment('1% of total, paid at end of loan tenor');
            $table->decimal('management_fee', 10, 2)->default(0.00)->after('residual_fee')->comment('1% management fee');
            $table->decimal('equity_contribution', 10, 2)->default(0.00)->after('management_fee')->comment('30% minimum upfront payment');
            $table->foreignId('state_id')->nullable()->after('delivery_address_id')->constrained('states')->onDelete('set null');
            $table->foreignId('delivery_location_id')->nullable()->after('state_id')->constrained('delivery_locations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['state_id']);
            $table->dropForeign(['delivery_location_id']);
            $table->dropColumn(['residual_fee', 'management_fee', 'equity_contribution', 'state_id', 'delivery_location_id']);
        });
    }
};
