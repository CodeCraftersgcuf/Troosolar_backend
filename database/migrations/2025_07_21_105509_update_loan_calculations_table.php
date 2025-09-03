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
        Schema::table('loan_calculations', function (Blueprint $table) {
            $table->double('product_amount', 10, 2)->default(0.0);
            $table->double('monthly_payment', 10, 2)->default(0.0);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_calculations', function (Blueprint $table) {
            //
        });
    }
};
