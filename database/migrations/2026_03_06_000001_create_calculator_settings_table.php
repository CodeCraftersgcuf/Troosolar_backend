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
        Schema::create('calculator_settings', function (Blueprint $table) {
            $table->id();
            $table->json('inverter_ranges')->nullable();
            $table->json('solar_savings_profiles')->nullable();
            $table->decimal('solar_maintenance_5_years', 15, 2)->default(150000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calculator_settings');
    }
};

