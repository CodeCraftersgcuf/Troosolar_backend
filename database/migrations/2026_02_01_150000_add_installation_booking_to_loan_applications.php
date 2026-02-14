<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->date('installation_requested_date')->nullable()->after('order_items_snapshot');
            $table->string('installation_booking_status', 20)->nullable()->after('installation_requested_date')->comment('pending, accepted, rejected');
            $table->json('installation_rejected_dates')->nullable()->after('installation_booking_status')->comment('Dates user requested that were rejected');
        });
    }

    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            $table->dropColumn(['installation_requested_date', 'installation_booking_status', 'installation_rejected_dates']);
        });
    }
};
