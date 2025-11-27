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
        Schema::create('audit_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable()->comment('Linked after payment confirmation');
            $table->string('audit_type')->comment('home-office, commercial');
            $table->string('customer_type')->nullable()->comment('residential, sme, commercial');
            $table->string('property_state')->nullable();
            $table->text('property_address')->nullable();
            $table->string('property_landmark')->nullable();
            $table->integer('property_floors')->nullable();
            $table->integer('property_rooms')->nullable();
            $table->boolean('is_gated_estate')->default(false);
            $table->string('estate_name')->nullable();
            $table->text('estate_address')->nullable();
            $table->string('status')->default('pending')->comment('pending, approved, rejected, completed');
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable()->comment('Admin user ID');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_requests');
    }
};
