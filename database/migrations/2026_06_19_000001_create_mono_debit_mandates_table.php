<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mono_debit_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mono_calculation_id')->nullable()->constrained('mono_loan_calculations')->nullOnDelete();
            $table->foreignId('loan_application_id')->nullable()->constrained('loan_applications')->nullOnDelete();
            $table->string('mono_mandate_id')->nullable()->index();
            $table->string('mono_customer_id')->nullable();
            $table->string('mono_account_id')->nullable();
            $table->string('reference')->unique();
            $table->string('status')->default('pending_authorization');
            $table->boolean('approved')->default(false);
            $table->boolean('ready_to_debit')->default(false);
            $table->string('authorization_url')->nullable();
            $table->unsignedBigInteger('amount_kobo')->default(0);
            $table->string('debit_type')->default('variable');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mono_debit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mono_debit_mandate_id')->constrained('mono_debit_mandates')->cascadeOnDelete();
            $table->foreignId('loan_installment_id')->nullable()->constrained('loan_installments')->nullOnDelete();
            $table->string('reference')->unique();
            $table->unsignedBigInteger('amount_kobo');
            $table->string('status')->default('pending');
            $table->json('mono_response')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('user_mono_accounts') && ! Schema::hasColumn('user_mono_accounts', 'mono_dd_customer_id')) {
            Schema::table('user_mono_accounts', function (Blueprint $table) {
                $table->string('mono_dd_customer_id')->nullable()->after('mono_customer_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mono_debit_transactions');
        Schema::dropIfExists('mono_debit_mandates');

        if (Schema::hasTable('user_mono_accounts') && Schema::hasColumn('user_mono_accounts', 'mono_dd_customer_id')) {
            Schema::table('user_mono_accounts', function (Blueprint $table) {
                $table->dropColumn('mono_dd_customer_id');
            });
        }
    }
};
