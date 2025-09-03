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
Schema::create('custom_services', function (Blueprint $table) {
    $table->id();
    $table->string('title')->nullable();
    $table->double('service_amount')->default(0.0);
    $table->foreignId('bundle_id')->constrained('bundles')->cascadeOnDelete();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_services');
    }
};
