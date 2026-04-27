<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Order list rows (admin BundleMgt) store per-line quantity, unit, and whether qty applies.
     */
    public function up(): void
    {
        Schema::table('custom_services', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_services', 'quantity')) {
                $table->unsignedInteger('quantity')->default(1);
            }
            if (!Schema::hasColumn('custom_services', 'unit')) {
                $table->string('unit', 32)->default('Nos');
            }
            if (!Schema::hasColumn('custom_services', 'quantity_applies')) {
                $table->boolean('quantity_applies')->default(true);
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_services', function (Blueprint $table) {
            if (Schema::hasColumn('custom_services', 'quantity_applies')) {
                $table->dropColumn('quantity_applies');
            }
            if (Schema::hasColumn('custom_services', 'unit')) {
                $table->dropColumn('unit');
            }
            if (Schema::hasColumn('custom_services', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};
