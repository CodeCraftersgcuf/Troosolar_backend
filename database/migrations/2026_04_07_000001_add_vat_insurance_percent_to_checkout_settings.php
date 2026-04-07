<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checkout_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('checkout_settings', 'vat_percentage')) {
                $table->decimal('vat_percentage', 5, 2)->default(7.50)->after('delivery_max_working_days');
            }
            if (! Schema::hasColumn('checkout_settings', 'insurance_fee_percentage')) {
                $table->decimal('insurance_fee_percentage', 5, 2)->default(3.00);
            }
            if (! Schema::hasColumn('checkout_settings', 'installation_flat_addon')) {
                $table->unsignedInteger('installation_flat_addon')->default(0)->after('installation_schedule_working_days');
            }
        });

        if (Schema::hasTable('checkout_settings') && DB::table('checkout_settings')->exists()) {
            DB::table('checkout_settings')->update([
                'vat_percentage' => 7.50,
                'insurance_fee_percentage' => 3.00,
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('checkout_settings', function (Blueprint $table) {
            foreach (['vat_percentage', 'insurance_fee_percentage', 'installation_flat_addon'] as $col) {
                if (Schema::hasColumn('checkout_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
