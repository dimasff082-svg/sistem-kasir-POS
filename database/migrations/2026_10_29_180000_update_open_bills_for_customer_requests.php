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
        Schema::table('open_bills', function (Blueprint $table) {
            $table->foreignId('opened_by')->nullable()->change();
            $table->string('customer_name')->nullable()->after('table_number');
            $table->string('snap_token')->nullable()->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('open_bills', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'snap_token']);
            $table->foreignId('opened_by')->nullable(false)->change();
        });
    }
};
