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
        Schema::create('add_ons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('type')->default('service')->comment('service, product, kit');
            $table->boolean('is_compulsory_bnpl')->default(false)->comment('Compulsory for BNPL customers');
            $table->boolean('is_compulsory_buy_now')->default(false)->comment('Compulsory for Buy Now customers');
            $table->boolean('is_optional')->default(true)->comment('Can be toggled by customer');
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
        Schema::dropIfExists('add_ons');
    }
};
