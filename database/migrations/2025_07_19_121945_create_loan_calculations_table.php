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
       Schema::create('loan_calculations', function (Blueprint $table) {
            $table->id();
            $table->double('loan_amount',10,2)->default(0.0);
            $table->integer('repayment_duration');
            $table->string('status')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();            $table->foreignId('interest_percentage_id')->constrained('interest_percentages')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_calculations');
    }
};
