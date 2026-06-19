<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mono_credit_check_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('mono_credit_check_sessions', 'mono_init_response')) {
                $table->json('mono_init_response')->nullable()->after('run_credit_check');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mono_credit_check_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('mono_credit_check_sessions', 'mono_init_response')) {
                $table->dropColumn('mono_init_response');
            }
        });
    }
};
