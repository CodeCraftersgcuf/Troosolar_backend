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
        Schema::create('debt_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('debt_status')->nullable();
            $table->double('total_owned')->default(0.0);
            $table->string('account_statement')->nullable();   
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debt_statuses');
    }
};
