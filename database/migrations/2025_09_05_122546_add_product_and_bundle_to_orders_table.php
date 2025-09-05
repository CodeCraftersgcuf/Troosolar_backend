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
             $table->foreignId('product_id')->nullable()->after('mono_calculation_id')
                  ->constrained('products')->nullOnDelete();

            $table->foreignId('bundle_id')->nullable()->after('product_id')
                  ->constrained('bundles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
                       $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');

            $table->dropForeign(['bundle_id']);
            $table->dropColumn('bundle_id');
        });
    }
};