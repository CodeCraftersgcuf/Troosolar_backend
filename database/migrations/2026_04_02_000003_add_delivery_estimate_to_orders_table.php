<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'estimated_delivery_from')) {
                $table->date('estimated_delivery_from')->nullable();
            }
            if (! Schema::hasColumn('orders', 'estimated_delivery_to')) {
                $table->date('estimated_delivery_to')->nullable();
            }
            if (! Schema::hasColumn('orders', 'delivery_estimate_label')) {
                $table->string('delivery_estimate_label', 120)->nullable();
            }
            if (! Schema::hasColumn('orders', 'include_installation')) {
                $table->boolean('include_installation')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['estimated_delivery_from', 'estimated_delivery_to', 'delivery_estimate_label', 'include_installation'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
