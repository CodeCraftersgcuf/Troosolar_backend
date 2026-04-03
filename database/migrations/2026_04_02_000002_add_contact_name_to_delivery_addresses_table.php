<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('delivery_addresses', 'contact_name')) {
            Schema::table('delivery_addresses', function (Blueprint $table) {
                $table->string('contact_name', 255)->nullable()->after('title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('delivery_addresses', 'contact_name')) {
            Schema::table('delivery_addresses', function (Blueprint $table) {
                $table->dropColumn('contact_name');
            });
        }
    }
};
