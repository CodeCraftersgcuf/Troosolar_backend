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
        Schema::create('bundle_custom_appliances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bundle_id')->constrained('bundles')->onDelete('cascade');
            $table->string('name');
            $table->decimal('wattage', 10, 2);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('estimated_daily_hours_usage', 5, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bundle_custom_appliances');
    }
};
