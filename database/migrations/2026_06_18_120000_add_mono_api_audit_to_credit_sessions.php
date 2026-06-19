<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mono_credit_check_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('mono_credit_check_sessions', 'api_request_payload')) {
                $table->json('api_request_payload')->nullable()->after('run_credit_check');
            }
            if (! Schema::hasColumn('mono_credit_check_sessions', 'api_init_response')) {
                $table->json('api_init_response')->nullable()->after('api_request_payload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mono_credit_check_sessions', function (Blueprint $table) {
            foreach (['api_init_response', 'api_request_payload'] as $col) {
                if (Schema::hasColumn('mono_credit_check_sessions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
