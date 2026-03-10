<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bnpl_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('bnpl_settings', 'down_payment_options')) {
                $table->json('down_payment_options')->nullable()->after('min_down_percentage');
            }
        });

        DB::table('bnpl_settings')
            ->whereNull('down_payment_options')
            ->update([
                'down_payment_options' => json_encode([30, 40, 50, 60, 70, 80]),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bnpl_settings', function (Blueprint $table) {
            if (Schema::hasColumn('bnpl_settings', 'down_payment_options')) {
                $table->dropColumn('down_payment_options');
            }
        });
    }
};
