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
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->string('title_document')->nullable();
            $table->string('upload_document')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->string('beneficiary_email')->nullable();
            $table->string('beneficiary_relationship')->nullable();
            $table->string('beneficiary_phone')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('mono_loan_calculation')->constrained('mono_loan_calculations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};
