<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checkout_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('delivery_fee')->default(2000);
            $table->unsignedTinyInteger('delivery_min_working_days')->default(7);
            $table->unsignedTinyInteger('delivery_max_working_days')->default(10);
            $table->unsignedInteger('insurance_fee')->default(0);
            $table->unsignedTinyInteger('installation_schedule_working_days')->default(7);
            $table->text('installation_description')->nullable();
            $table->timestamps();
        });

        DB::table('checkout_settings')->insert([
            'delivery_fee' => 2000,
            'delivery_min_working_days' => 7,
            'delivery_max_working_days' => 10,
            'insurance_fee' => 0,
            'installation_schedule_working_days' => 7,
            'installation_description' => 'Installation will be carried out by our skilled technicians. You can choose to use our installers.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_settings');
    }
};
