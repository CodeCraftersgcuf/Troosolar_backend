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
        Schema::create('delivery_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')->nullable()->constrained('states')->onDelete('set null');
            $table->foreignId('local_government_id')->nullable()->constrained('local_governments')->onDelete('set null');
            $table->string('name')->comment('e.g., Lagos Island, Lagos Mainland');
            $table->decimal('delivery_fee', 10, 2)->default(25000.00);
            $table->decimal('installation_fee', 10, 2)->nullable()->comment('Override state/LGA default if set');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_locations');
    }
};
