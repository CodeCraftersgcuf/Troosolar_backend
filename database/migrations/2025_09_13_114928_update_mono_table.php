<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mono_loan_calculations', function (Blueprint $table) {
            $table->string('interest_rate')->nullable();
            $table->double('total_amount')->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mono_loan_calculations', function (Blueprint $table) {
            //
        });
    }
};
