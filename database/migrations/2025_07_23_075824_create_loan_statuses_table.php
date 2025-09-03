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
        Schema::create('loan_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('send_status')->default('pending');
            $table->date('send_date')->nullable();
            $table->string('approval_status')->default('pending');
            $table->date('approval_date')->nullable();
            $table->string('disbursement_status')->default('pending');
            $table->date('disbursement_date')->nullable();
            $table->foreignId('loan_application_id')
                ->constrained('loan_applications')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_statuses');
    }
};
