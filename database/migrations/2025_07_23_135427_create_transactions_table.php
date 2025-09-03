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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
        $table->string('title'); // e.g., "Order Payment - Direct"
        $table->decimal('amount', 12, 2);
        $table->string('status'); // e.g., Completed, Pending
        $table->string('type'); // e.g., incoming/outgoing
        $table->string('method')->nullable(); // e.g., Loan, Direct
        $table->timestamp('transacted_at');
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');                                                                           
    }
};