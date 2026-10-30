<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bersihkan atau reassign user yang sebelumnya memiliki role supervisor
        DB::table('users')->where('role', 'supervisor')->delete();

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'owner', 'kitchen') NOT NULL DEFAULT 'cashier'");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('cashier')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'cashier', 'owner', 'supervisor', 'kitchen') NOT NULL DEFAULT 'cashier'");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('cashier')->change();
            });
        }
    }
};
