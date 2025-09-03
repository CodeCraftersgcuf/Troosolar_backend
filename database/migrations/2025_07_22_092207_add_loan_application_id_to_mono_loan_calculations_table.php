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
              $table->unsignedBigInteger('loan_application_id')->nullable()->after('id');

        $table->foreign('loan_application_id')
              ->references('id')->on('loan_applications')
              ->onDelete('cascade');
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