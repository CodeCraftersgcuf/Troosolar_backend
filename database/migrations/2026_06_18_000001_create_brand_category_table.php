<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brand_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['brand_id', 'category_id']);
        });

        if (Schema::hasTable('brands') && Schema::hasColumn('brands', 'category_id')) {
            $rows = DB::table('brands')
                ->whereNotNull('category_id')
                ->select(['id', 'category_id'])
                ->get();

            $now = now();
            foreach ($rows as $row) {
                DB::table('brand_category')->insertOrIgnore([
                    'brand_id' => $row->id,
                    'category_id' => $row->category_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_category');
    }
};
