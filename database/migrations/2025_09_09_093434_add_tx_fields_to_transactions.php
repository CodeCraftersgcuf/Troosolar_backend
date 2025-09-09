<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'tx_id')) {
                $table->string('tx_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('transactions', 'reference')) {
                $table->string('reference')->nullable()->index()->after('tx_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'reference')) $table->dropColumn('reference');
            if (Schema::hasColumn('transactions', 'tx_id')) $table->dropColumn('tx_id');
        });
    }
};