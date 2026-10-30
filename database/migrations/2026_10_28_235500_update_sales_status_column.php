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
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `sales` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
        } else {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('status', 50)->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `sales` MODIFY COLUMN `status` ENUM('completed', 'pending_cancellation', 'cancelled') NOT NULL DEFAULT 'completed'");
        } else {
            Schema::table('sales', function (Blueprint $table) {
                $table->string('status')->default('completed')->change();
            });
        }
    }
};
