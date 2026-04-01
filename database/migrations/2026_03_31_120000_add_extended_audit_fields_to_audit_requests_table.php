<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->string('audit_subtype')->nullable()->after('audit_type')->comment('home, office when audit_type is home-office');
            $table->string('company_name')->nullable()->after('customer_type');
            $table->string('building_type')->nullable()->after('property_landmark');
            $table->text('facility_description')->nullable()->after('building_type');
        });
    }

    public function down(): void
    {
        Schema::table('audit_requests', function (Blueprint $table) {
            $table->dropColumn(['audit_subtype', 'company_name', 'building_type', 'facility_description']);
        });
    }
};
