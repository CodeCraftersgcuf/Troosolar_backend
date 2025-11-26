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
            $table->decimal('material_cost', 10, 2)->default(0.00)->after('installation_price');
            $table->decimal('delivery_fee', 10, 2)->default(0.00)->after('material_cost');
            $table->decimal('inspection_fee', 10, 2)->default(0.00)->after('delivery_fee');
            $table->decimal('insurance_fee', 10, 2)->default(0.00)->after('inspection_fee');
            $table->string('order_type')->default('buy_now')->comment('buy_now, bnpl, audit_only')->after('insurance_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'material_cost',
                'delivery_fee',
                'inspection_fee',
                'insurance_fee',
                'order_type'
            ]);
        });
    }
};
