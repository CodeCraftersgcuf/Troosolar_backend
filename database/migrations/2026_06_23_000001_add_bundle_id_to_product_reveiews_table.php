<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reveiews', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product_reveiews MODIFY product_id BIGINT UNSIGNED NULL');
        } else {
            Schema::table('product_reveiews', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable()->change();
            });
        }

        Schema::table('product_reveiews', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
            $table->foreignId('bundle_id')->nullable()->after('product_id')->constrained('bundles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_reveiews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bundle_id');
            $table->dropForeign(['product_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE product_reveiews MODIFY product_id BIGINT UNSIGNED NOT NULL');
        } else {
            Schema::table('product_reveiews', function (Blueprint $table) {
                $table->unsignedBigInteger('product_id')->nullable(false)->change();
            });
        }

        Schema::table('product_reveiews', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }
};
