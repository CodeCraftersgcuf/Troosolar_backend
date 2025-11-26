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
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->string('customer_type')->nullable()->comment('residential, sme, commercial');
            $table->string('product_category')->nullable();
            $table->string('audit_type')->nullable()->comment('home-office, commercial');
            $table->string('property_state')->nullable();
            $table->text('property_address')->nullable();
            $table->string('property_landmark')->nullable();
            $table->integer('property_floors')->nullable();
            $table->integer('property_rooms')->nullable();
            $table->boolean('is_gated_estate')->default(false);
            $table->string('estate_name')->nullable();
            $table->text('estate_address')->nullable();
            $table->string('credit_check_method')->nullable()->comment('auto, manual');
            $table->string('bank_statement_path')->nullable();
            $table->string('live_photo_path')->nullable();
            $table->string('social_media_handle')->nullable();
            $table->unsignedBigInteger('guarantor_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropColumn([
                'customer_type',
                'product_category',
                'audit_type',
                'property_state',
                'property_address',
                'property_landmark',
                'property_floors',
                'property_rooms',
                'is_gated_estate',
                'estate_name',
                'estate_address',
                'credit_check_method',
                'bank_statement_path',
                'live_photo_path',
                'social_media_handle',
                'guarantor_id'
            ]);
        });
    }
};
