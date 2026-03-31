<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('loan_applications', 'prior_application_id')) {
                $table->unsignedBigInteger('prior_application_id')->nullable()->after('user_id');
                $table->foreign('prior_application_id')
                    ->references('id')
                    ->on('loan_applications')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            if (Schema::hasColumn('loan_applications', 'prior_application_id')) {
                $table->dropForeign(['prior_application_id']);
                $table->dropColumn('prior_application_id');
            }
        });
    }
};
