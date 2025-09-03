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
       Schema::create('mono_loan_calculations', function (Blueprint $table) {
            $table->id();
             $table->double('down_payment', 10, 2)->default(0.0);
            $table->foreignId('loan_calculation_id')->constrained('loan_calculations')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mono_loan_calculations');
    }
};
