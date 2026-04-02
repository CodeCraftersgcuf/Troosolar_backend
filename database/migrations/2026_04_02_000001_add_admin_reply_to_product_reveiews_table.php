<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reveiews', function (Blueprint $table) {
            $table->text('admin_reply')->nullable()->after('rating');
            $table->timestamp('admin_replied_at')->nullable()->after('admin_reply');
        });
    }

    public function down(): void
    {
        Schema::table('product_reveiews', function (Blueprint $table) {
            $table->dropColumn(['admin_reply', 'admin_replied_at']);
        });
    }
};
