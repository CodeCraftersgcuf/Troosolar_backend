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
        Schema::table('loan_installments', function (Blueprint $t) {
            $t->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete()->after('amount');
            $t->dateTime('paid_at')->nullable()->after('transaction_id');
            $t->enum('status', ['pending','paid'])->default('pending')->change(); // if needed
            $t->dateTime('payment_date')->nullable()->change(); // ensure present
        });
    }

    public function down(): void
    {
        Schema::table('loan_installments', function (Blueprint $t) {
            $t->dropConstrainedForeignId('transaction_id');
            $t->dropColumn('paid_at');
        });
    }
};
