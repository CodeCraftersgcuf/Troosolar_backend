<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_calculations', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['interest_percentage_id']);

            // Then drop the column
            $table->dropColumn('interest_percentage_id');
        });
    }

    public function down(): void
    {
        Schema::table('loan_calculations', function (Blueprint $table) {
            // Re-add the column and foreign key in case of rollback
            $table->foreignId('interest_percentage_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('cascade');
        });
    }
};
