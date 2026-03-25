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
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('customer_type')->comment('buy_now, bnpl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
