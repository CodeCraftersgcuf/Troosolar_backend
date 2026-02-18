<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bundle_items', function (Blueprint $table) {
            $table->integer('quantity')->default(1)->after('product_id');
            $table->decimal('rate_override', 15, 2)->nullable()->after('quantity');
        });

        Schema::table('bundle_materials', function (Blueprint $table) {
            $table->decimal('rate_override', 15, 2)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('bundle_items', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'rate_override']);
        });

        Schema::table('bundle_materials', function (Blueprint $table) {
            $table->dropColumn('rate_override');
        });
    }
};
