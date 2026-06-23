<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'Admin')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'Super_Admin')->update(['role' => 'super_admin']);
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        // Role normalization is not reversed automatically.
    }
};
