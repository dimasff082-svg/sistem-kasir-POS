<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Support MySQL enum modification or string fallback for SQLite in tests
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'owner', 'supervisor', 'kitchen') NOT NULL DEFAULT 'cashier'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'owner', 'supervisor') NOT NULL DEFAULT 'cashier'");
        }
    }
};
