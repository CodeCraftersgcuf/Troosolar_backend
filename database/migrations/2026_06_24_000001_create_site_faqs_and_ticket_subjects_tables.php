<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('site_faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        DB::table('ticket_subjects')->insert([
            ['title' => 'Order & delivery', 'sort_order' => 1, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'BNPL / loan application', 'sort_order' => 2, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Product inquiry', 'sort_order' => 3, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Installation', 'sort_order' => 4, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Technical support', 'sort_order' => 5, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Billing & payments', 'sort_order' => 6, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Account issues', 'sort_order' => 7, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Other', 'sort_order' => 99, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::table('site_faqs')->insert([
            [
                'question' => 'What is Buy Now, Pay Later (BNPL)?',
                'answer' => 'BNPL lets you spread the cost of your solar system over monthly instalments after an initial deposit. Apply from the dashboard, complete KYC, and our team will guide you through approval and installation.',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => 'How long does delivery take?',
                'answer' => 'Delivery typically takes 7–10 working days after your order is confirmed and payment requirements are met. You will see an estimated delivery window on your order summary.',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => 'Do I need a site audit before installation?',
                'answer' => 'For many installations we recommend a site audit so our engineers can confirm roof space, load requirements, and cabling. You can request an audit from the Audit section in your dashboard.',
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => 'How do I track my order?',
                'answer' => 'Go to More → My Orders in your dashboard. Open any order to see status, payment breakdown, and delivery details.',
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => 'Who do I contact for help?',
                'answer' => 'Open More → Support to raise a ticket. Choose a subject, describe your issue, and our team will respond in the ticket thread.',
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('site_faqs');
        Schema::dropIfExists('ticket_subjects');
    }
};
