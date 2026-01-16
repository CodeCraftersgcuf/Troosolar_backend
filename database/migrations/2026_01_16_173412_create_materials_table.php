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
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_category_id')->constrained('material_categories')->onDelete('cascade');
            $table->string('name'); // Description of material
            $table->string('unit'); // Nos, Mtrs, etc.
            $table->integer('warranty')->nullable(); // Warranty in years
            $table->decimal('rate', 15, 2)->default(0.00); // Cost rate
            $table->decimal('selling_rate', 15, 2)->default(0.00); // Selling rate
            $table->decimal('profit', 15, 2)->default(0.00); // Profit amount
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};
