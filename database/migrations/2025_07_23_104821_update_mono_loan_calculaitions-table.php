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
            $table->double('loan_limit')->default(0.0);
            $table->string('credit_score')->nullable();
            $table->string('transcations')->nullable();
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
