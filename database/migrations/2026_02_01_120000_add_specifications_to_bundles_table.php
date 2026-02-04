<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds specification tab data for inverter/battery bundles (e.g. inverter capacity, voltage, warranty).
     */
    public function up(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->json('specifications')->nullable()->after('total_output');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            $table->dropColumn('specifications');
        });
    }
};
