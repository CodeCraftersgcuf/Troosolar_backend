<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Safely adds missing columns to orders table
     */
    public function up(): void
    {
        // Use raw SQL to safely add columns without dependency on 'after' clause
        $columns = [
            'installation_price' => "ALTER TABLE `orders` ADD COLUMN `installation_price` DECIMAL(10,2) DEFAULT 0 NULL",
            'material_cost' => "ALTER TABLE `orders` ADD COLUMN `material_cost` DECIMAL(10,2) DEFAULT 0.00 NULL",
            'delivery_fee' => "ALTER TABLE `orders` ADD COLUMN `delivery_fee` DECIMAL(10,2) DEFAULT 0.00 NULL",
            'inspection_fee' => "ALTER TABLE `orders` ADD COLUMN `inspection_fee` DECIMAL(10,2) DEFAULT 0.00 NULL",
            'insurance_fee' => "ALTER TABLE `orders` ADD COLUMN `insurance_fee` DECIMAL(10,2) DEFAULT 0.00 NULL",
            'order_type' => "ALTER TABLE `orders` ADD COLUMN `order_type` VARCHAR(50) DEFAULT 'buy_now' NULL COMMENT 'buy_now, bnpl, audit_only'",
        ];

        foreach ($columns as $columnName => $sql) {
            if (!Schema::hasColumn('orders', $columnName)) {
                DB::statement($sql);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop columns in down migration to avoid data loss
        // If needed, create a separate migration to drop these columns
    }
};

