<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mono_credit_check_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('mono_account_id')->nullable()->index();
            $table->string('mono_customer_id')->nullable();
            $table->string('bvn', 20);
            $table->unsignedBigInteger('principal_kobo');
            $table->decimal('interest_rate', 8, 2);
            $table->unsignedSmallInteger('term_months');
            $table->boolean('run_credit_check')->default(true);
            $table->string('status', 32)->default('pending')->index();
            $table->boolean('can_afford')->nullable();
            $table->unsignedBigInteger('monthly_payment_kobo')->nullable();
            $table->json('credit_worthiness_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('loan_application_id')->nullable()->constrained('loan_applications')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mono_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->string('mono_account_id')->nullable()->index();
            $table->string('payload_hash', 64);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['event', 'mono_account_id', 'payload_hash'], 'mono_webhook_events_unique');
        });

        Schema::table('loan_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('loan_applications', 'mono_account_id')) {
                $table->string('mono_account_id')->nullable()->after('credit_check_method');
            }
            if (! Schema::hasColumn('loan_applications', 'mono_customer_id')) {
                $table->string('mono_customer_id')->nullable()->after('mono_account_id');
            }
            if (! Schema::hasColumn('loan_applications', 'mono_credit_status')) {
                $table->string('mono_credit_status', 32)->nullable()->after('mono_customer_id');
            }
            if (! Schema::hasColumn('loan_applications', 'mono_can_afford')) {
                $table->boolean('mono_can_afford')->nullable()->after('mono_credit_status');
            }
            if (! Schema::hasColumn('loan_applications', 'mono_monthly_payment_kobo')) {
                $table->unsignedBigInteger('mono_monthly_payment_kobo')->nullable()->after('mono_can_afford');
            }
            if (! Schema::hasColumn('loan_applications', 'mono_credit_report')) {
                $table->json('mono_credit_report')->nullable()->after('mono_monthly_payment_kobo');
            }
            if (! Schema::hasColumn('loan_applications', 'mono_credit_session_id')) {
                $table->foreignId('mono_credit_session_id')->nullable()->after('mono_credit_report')
                    ->constrained('mono_credit_check_sessions')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('loan_applications', function (Blueprint $table) {
            if (Schema::hasColumn('loan_applications', 'mono_credit_session_id')) {
                $table->dropForeign(['mono_credit_session_id']);
                $table->dropColumn('mono_credit_session_id');
            }
            foreach (['mono_credit_report', 'mono_monthly_payment_kobo', 'mono_can_afford', 'mono_credit_status', 'mono_customer_id', 'mono_account_id'] as $col) {
                if (Schema::hasColumn('loan_applications', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('mono_webhook_events');
        Schema::dropIfExists('mono_credit_check_sessions');
    }
};
