<?php

namespace Tests\Unit;

use App\Models\Sale;
use App\Models\User;
use App\Support\UnifiedTransactionRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantSystemUnitTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan label role user terdefinisi dengan tepat.
     */
    public function test_user_role_labels(): void
    {
        $admin = new User(['role' => 'admin']);
        $this->assertEquals('ADMINISTRATOR', $admin->roleLabel());

        $owner = new User(['role' => 'owner']);
        $this->assertEquals('PEMILIK TOKO', $owner->roleLabel());

        $cashier = new User(['role' => 'cashier']);
        $this->assertEquals('KASIR', $cashier->roleLabel());

        $kitchen = new User(['role' => 'kitchen']);
        $this->assertEquals('DAPUR', $kitchen->roleLabel());
    }

    /**
     * Memastikan label status pesanan dapur pada model Sale sesuai standar sistem.
     */
    public function test_sale_order_status_labels(): void
    {
        $salePending = new Sale(['order_status' => 'pending']);
        $this->assertEquals('Menunggu', $salePending->orderStatusLabel());

        $saleProcessing = new Sale(['order_status' => 'processing']);
        $this->assertEquals('Diproses', $saleProcessing->orderStatusLabel());

        $saleCompleted = new Sale(['order_status' => 'completed']);
        $this->assertEquals('Selesai', $saleCompleted->orderStatusLabel());
    }

    /**
     * Memastikan UnifiedTransactionRecord memetakan record transaksi reguler dengan benar.
     */
    public function test_unified_transaction_record_from_regular_sale(): void
    {
        $sale = new Sale([
            'transaction_number' => 'ORD-TEST-001',
            'table_number' => 'M001',
            'customer_name' => 'Budi',
            'subtotal' => 50000,
            'discount_amount' => 0,
            'total_amount' => 50000,
            'status' => 'completed',
            'payment_status' => 'paid',
        ]);
        $sale->id = 99;

        $record = UnifiedTransactionRecord::fromSale($sale);

        $this->assertFalse($record->is_open_bill);
        $this->assertEquals('Reguler', $record->badge_label);
        $this->assertEquals('ORD-TEST-001', $record->transaction_number);
        $this->assertEquals('M001', $record->table_number);
        $this->assertEquals(50000.0, $record->total_amount);
        $this->assertEquals('sale_99', $record->accordion_id);
    }
}
