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
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->string('table_number')->nullable()->index()->after('transaction_number');
            $table->string('customer_name')->nullable()->after('table_number');
            $table->string('order_status')->default('pending')->after('status'); // pending (Menunggu), processing (Diproses), completed (Selesai)
            $table->string('payment_status')->default('unpaid')->after('order_status'); // unpaid (Belum Dibayar), paid (Lunas)
            $table->foreignId('open_bill_id')->nullable()->after('payment_status')->constrained('open_bills')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->after('open_bill_id')->constrained('users')->nullOnDelete();
            $table->dateTime('confirmed_at')->nullable()->after('confirmed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['open_bill_id']);
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn([
                'table_number',
                'customer_name',
                'order_status',
                'payment_status',
                'open_bill_id',
                'confirmed_by',
                'confirmed_at',
            ]);
        });
    }
};
