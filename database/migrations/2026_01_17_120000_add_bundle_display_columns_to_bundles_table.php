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
        Schema::table('bundles', function (Blueprint $table) {
            $table->string('product_model', 255)->nullable()->after('bundle_type');
            $table->string('system_capacity_display', 255)->nullable()->after('product_model');
            $table->text('detailed_description')->nullable()->after('system_capacity_display');
            $table->text('what_is_inside_bundle_text')->nullable()->after('detailed_description');
            $table->text('what_bundle_powers_text')->nullable()->after('what_is_inside_bundle_text');
            $table->text('backup_time_description')->nullable()->after('what_bundle_powers_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->dropColumn([
                'product_model',
                'system_capacity_display',
                'detailed_description',
                'what_is_inside_bundle_text',
                'what_bundle_powers_text',
                'backup_time_description',
            ]);
        });
    }
};
