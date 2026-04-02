<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            if (!Schema::hasColumn('bundles', 'top_deal')) {
                $table->boolean('top_deal')->default(false);
            }
            if (!Schema::hasColumn('bundles', 'is_most_popular')) {
                $table->boolean('is_most_popular')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('bundles', function (Blueprint $table) {
            if (Schema::hasColumn('bundles', 'is_most_popular')) {
                $table->dropColumn('is_most_popular');
            }
            if (Schema::hasColumn('bundles', 'top_deal')) {
                $table->dropColumn('top_deal');
            }
        });
    }
};
