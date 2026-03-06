<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_available')->default(true)->after('installation_compulsory');
            $table->text('description')->nullable()->after('featured_image');
            $table->text('specifications')->nullable()->after('description');
        });

        Schema::table('bundles', function (Blueprint $table) {
            $table->boolean('is_available')->default(true)->after('bundle_type');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_available', 'description', 'specifications']);
        });

        Schema::table('bundles', function (Blueprint $table) {
            $table->dropColumn(['is_available']);
        });
    }
};

