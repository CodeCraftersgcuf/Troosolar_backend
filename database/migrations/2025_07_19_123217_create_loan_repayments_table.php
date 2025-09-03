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
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->double('amount', 10, 2)->default(0.0);
            $table->string('status')->nullable();
             $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); 
            $table->foreignId('mono_calculation_id')->constrained('mono_loan_calculations')->cascadeOnDelete(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
