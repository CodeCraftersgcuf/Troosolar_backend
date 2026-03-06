<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calculator_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('calculator_settings', 'bundle_types')) {
                $table->json('bundle_types')->nullable()->after('solar_savings_profiles');
            }
        });
    }

    public function down(): void
    {
        Schema::table('calculator_settings', function (Blueprint $table) {
            if (Schema::hasColumn('calculator_settings', 'bundle_types')) {
                $table->dropColumn('bundle_types');
            }
        });
    }
};

