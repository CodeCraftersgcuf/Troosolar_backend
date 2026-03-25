<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer-selected installation date (Buy Now flow), visible in admin like BNPL.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'installation_requested_date')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->date('installation_requested_date')->nullable()->after('note');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'installation_requested_date')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('installation_requested_date');
            });
        }
    }
};
