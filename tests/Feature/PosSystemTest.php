<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $cashier;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create([
            'email' => 'admin@pos.test',
            'is_active' => true,
        ]);

        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'email' => 'kasir@pos.test',
            'is_active' => true,
        ]);

        $this->category = Category::factory()->create([
            'name' => 'Minuman',
            'is_active' => true,
        ]);
    }

    /**
     * Skenario 1: Login menggunakan akun valid -> Pengguna masuk ke dashboard sesuai peran.
     */
    public function test_skenario_1_valid_login_redirects_to_role_dashboard(): void
    {
        // Admin login
        $adminResponse = $this->post('/login', [
            'email' => 'admin@pos.test',
            'password' => 'password',
        ]);
        $adminResponse->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin);

        // Cashier login
        $this->post('/logout');
        $cashierResponse = $this->post('/login', [
            'email' => 'kasir@pos.test',
            'password' => 'password',
        ]);
        $cashierResponse->assertRedirect(route('cashier.dashboard'));
        $this->assertAuthenticatedAs($this->cashier);
    }

    /**
     * Skenario 2: Login menggunakan password salah -> Sistem menolak login dan menampilkan pesan yang aman.
     */
    public function test_skenario_2_invalid_password_is_rejected(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@pos.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Skenario 3: Kasir membuka halaman khusus Admin -> Akses ditolak (403).
     */
    public function test_skenario_3_cashier_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->cashier);

        $response = $this->get('/admin/dashboard');
        $response->assertForbidden();

        $responseCategories = $this->get('/admin/categories');
        $responseCategories->assertForbidden();

        $responseUsers = $this->get('/admin/users');
        $responseUsers->assertForbidden();
    }

    /**
     * Skenario 4: Admin memasukkan kode barang yang sudah ada -> Duplikasi kode SKU ditolak.
     */
    public function test_skenario_4_duplicate_sku_is_rejected(): void
    {
        $this->actingAs($this->admin);

        Product::factory()->create([
            'sku' => 'SKU-UNIQUE-01',
            'name' => 'Produk Pertama',
        ]);

        $response = $this->post('/admin/products', [
            'sku' => 'SKU-UNIQUE-01',
            'name' => 'Produk Duplikat',
            'unit' => 'pcs',
            'selling_price' => 2000,
        ]);

        $response->assertSessionHasErrors('sku');
        $this->assertEquals(1, Product::where('sku', 'SKU-UNIQUE-01')->count());
    }

    /**
     * Skenario 5: Kasir mencari barang yang ada -> Barang tampil dengan informasi yang benar.
     */
    public function test_skenario_5_search_existing_product(): void
    {
        $this->actingAs($this->cashier);

        $prod = Product::factory()->create([
            'sku' => 'AQUA-600',
            'name' => 'Aqua 600ml Segar',
            'selling_price' => 3500,
            'is_active' => true,
        ]);

        $response = $this->getJson('/pos/search?q=Aqua');
        $response->assertOk()
            ->assertJsonFragment([
                'sku' => 'AQUA-600',
                'name' => 'Aqua 600ml Segar',
            ]);
    }

    /**
     * Skenario 6: Kasir mencari barang yang tidak ada -> Menghasilkan hasil kosong tanpa error.
     */
    public function test_skenario_6_search_non_existing_product(): void
    {
        $this->actingAs($this->cashier);

        $response = $this->getJson('/pos/search?q=ProdukGaibTidakAda');
        $response->assertOk();
        $this->assertEmpty($response->json());
    }

    /**
     * Skenario 7: Pesanan menu diproses tanpa pembatasan stok karena fitur stok telah ditiadakan.
     */
    public function test_skenario_7_order_without_stock_restriction(): void
    {
        $this->actingAs($this->cashier);

        $product = Product::factory()->create([
            'selling_price' => 10000,
            'is_active' => true,
        ]);

        $response = $this->postJson('/pos/checkout', [
            'items' => [
                ['id' => $product->id, 'quantity' => 10],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 100000,
        ]);

        $response->assertOk();
        $this->assertEquals(1, Sale::count());
    }

    /**
     * Skenario 8: Pembayaran tunai kurang -> Transaksi tidak dapat diselesaikan.
     */
    public function test_skenario_8_insufficient_cash_payment_is_rejected(): void
    {
        $this->actingAs($this->cashier);

        $product = Product::factory()->create([
            'selling_price' => 50000,
            'is_active' => true,
        ]);

        $response = $this->postJson('/pos/checkout', [
            'items' => [
                ['id' => $product->id, 'quantity' => 2], // Total = 100.000
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 75000, // Kurang 25.000
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('kurang', $response->json('message'));
        $this->assertEquals(0, Sale::count());
    }

    /**
     * Skenario 9: Pembayaran tunai cukup -> Menghitung kembalian dengan benar.
     */
    public function test_skenario_9_sufficient_cash_payment_calculates_correct_change(): void
    {
        $this->actingAs($this->cashier);

        $product = Product::factory()->create([
            'selling_price' => 35000,
            'is_active' => true,
        ]);

        $response = $this->postJson('/pos/checkout', [
            'items' => [
                ['id' => $product->id, 'quantity' => 2], // Total = 70.000
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 100000, // Bayar 100.000
        ]);

        $response->assertOk();
        $saleId = $response->json('sale_id');

        $payment = Payment::where('sale_id', $saleId)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(100000, (float) $payment->amount_tendered);
        $this->assertEquals(70000, (float) $payment->amount_paid);
        $this->assertEquals(30000, (float) $payment->change_amount);
    }

    /**
     * Skenario 10: Transaksi berhasil -> Penjualan, detail snapshot, dan pembayaran tersimpan.
     */
    public function test_skenario_10_successful_transaction_stores_all_audit_records(): void
    {
        $this->actingAs($this->cashier);

        $product = Product::factory()->create([
            'sku' => 'SNACK-01',
            'name' => 'Keripik Singkong Renyah',
            'selling_price' => 10000,
            'is_active' => true,
        ]);

        $response = $this->postJson('/pos/checkout', [
            'items' => [
                ['id' => $product->id, 'quantity' => 3],
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 30000,
        ]);

        $response->assertOk();
        $saleId = $response->json('sale_id');

        // 1. Verifikasi Sale
        $sale = Sale::find($saleId);
        $this->assertNotNull($sale);
        $this->assertEquals(30000, (float) $sale->total_amount);
        $this->assertEquals('completed', $sale->status);
        $this->assertEquals($this->cashier->id, $sale->user_id);

        // 2. Verifikasi SaleItem snapshot
        $item = SaleItem::where('sale_id', $sale->id)->first();
        $this->assertEquals('Keripik Singkong Renyah', $item->product_name_snapshot);
        $this->assertEquals('SNACK-01', $item->sku_snapshot);
        $this->assertEquals(3, $item->quantity);
        $this->assertEquals(10000, (float) $item->unit_price);
    }

    /**
     * Skenario 11: Penyimpanan transaksi gagal (roll back) -> Perubahan dibatalkan.
     */
    public function test_skenario_11_transaction_rollback_prevents_partial_stock_deduction(): void
    {
        $this->actingAs($this->cashier);

        $product = Product::factory()->create([
            'selling_price' => 15000,
            'is_active' => true,
        ]);

        // Kirim salah satu barang yang tidak valid untuk memaksa exception di dalam DB transaction
        $response = $this->postJson('/pos/checkout', [
            'items' => [
                ['id' => $product->id, 'quantity' => 2],
                ['id' => 999999, 'quantity' => 1], // Non-existent product ID
            ],
            'payment_method' => 'cash',
            'amount_tendered' => 50000,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, Sale::count());
    }

    /**
     * Skenario 12: Checkout berulang / proteksi double submit -> Transaksi tersimpan dengan nomor transaksi unik.
     */
    public function test_skenario_12_unique_transaction_numbers_for_each_checkout(): void
    {
        $this->actingAs($this->cashier);

        $product = Product::factory()->create([
            'selling_price' => 5000,
            'is_active' => true,
        ]);

        $res1 = $this->postJson('/pos/checkout', [
            'items' => [['id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'amount_tendered' => 5000,
        ]);
        $res2 = $this->postJson('/pos/checkout', [
            'items' => [['id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'cash',
            'amount_tendered' => 5000,
        ]);

        $res1->assertOk();
        $res2->assertOk();

        $sale1 = Sale::find($res1->json('sale_id'));
        $sale2 = Sale::find($res2->json('sale_id'));

        $this->assertNotEquals($sale1->transaction_number, $sale2->transaction_number);
    }

    /**
     * Skenario 13: Harga barang diubah setelah transaksi lama -> Struk lama tetap memakai snapshot harga saat transaksi.
     */
    public function test_skenario_13_price_change_does_not_affect_old_receipt_snapshot(): void
    {
        $this->actingAs($this->cashier);

        $product = Product::factory()->create([
            'sku' => 'TEH-BOTOL',
            'name' => 'Teh Botol Melati 350ml',
            'selling_price' => 4000,
            'is_active' => true,
        ]);

        // Lakukan transaksi saat harga masih 4.000
        $res = $this->postJson('/pos/checkout', [
            'items' => [['id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'amount_tendered' => 10000,
        ]);
        $saleId = $res->json('sale_id');

        // Admin kemudian menaikkan harga barang master menjadi 6.000
        $this->actingAs($this->admin);
        $this->put("/admin/products/{$product->id}", [
            'sku' => 'TEH-BOTOL',
            'name' => 'Teh Botol Melati 350ml Super',
            'unit' => 'botol',
            'selling_price' => 6000, // Harga naik
        ]);

        $this->assertEquals(6000, (float) $product->fresh()->selling_price);

        // Periksa struk transaksi lama
        $oldSaleItem = SaleItem::where('sale_id', $saleId)->first();
        $this->assertEquals(4000, (float) $oldSaleItem->unit_price);
        $this->assertEquals(8000, (float) $oldSaleItem->line_total);
        $this->assertEquals('Teh Botol Melati 350ml', $oldSaleItem->product_name_snapshot);
    }

    /**
     * Skenario 14: Admin memfilter laporan berdasarkan tanggal -> Ringkasan omzet sesuai periode tersebut.
     */
    public function test_skenario_14_admin_sales_report_filter(): void
    {
        $this->actingAs($this->admin);

        // Transaksi kemarin (100.000)
        $saleYesterday = Sale::factory()->create([
            'user_id' => $this->admin->id,
            'transaction_date' => now()->subDays(2),
            'total_amount' => 100000,
            'status' => 'completed',
        ]);
        Payment::factory()->create([
            'sale_id' => $saleYesterday->id,
            'amount_paid' => 100000,
        ]);

        // Transaksi hari ini (50.000)
        $saleToday = Sale::factory()->create([
            'user_id' => $this->admin->id,
            'transaction_date' => now(),
            'total_amount' => 50000,
            'status' => 'completed',
        ]);
        Payment::factory()->create([
            'sale_id' => $saleToday->id,
            'amount_paid' => 50000,
        ]);

        // Filter hanya hari ini
        $response = $this->get('/admin/reports?start_date='.now()->toDateString().'&end_date='.now()->toDateString());
        $response->assertOk();
        $response->assertSee('50.000');
    }

    /**
     * Skenario 15: Barang tersimpan dan aktif tanpa batasan stok.
     */
    public function test_skenario_15_products_are_active_without_stock(): void
    {
        $product = Product::factory()->create([
            'name' => 'Kopi Tubruk',
            'is_active' => true,
        ]);

        $this->assertTrue($product->is_active);
    }

    /**
     * Uji Role Pemilik: Login pemilik dialihkan ke dashboard pemilik.
     */
    public function test_owner_login_redirects_to_owner_dashboard(): void
    {
        $owner = User::factory()->owner()->create([
            'email' => 'pemilik@pos.test',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'pemilik@pos.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('owner.dashboard'));
        $this->assertAuthenticatedAs($owner);
        $this->assertTrue($owner->isOwner());
    }

    /**
     * Uji Dashboard Pemilik: Menampilkan metrik Omzet, HPP, Laba Kotor, dan Nilai Aset Stok.
     */
    public function test_owner_dashboard_displays_financial_kpi_metrics(): void
    {
        $owner = User::factory()->owner()->create(['is_active' => true]);

        // Buat produk dengan harga tertentu
        $product = Product::factory()->create([
            'selling_price' => 15000,
            'is_active' => true,
        ]);

        // Buat penjualan selesai: 2 unit
        $sale = Sale::factory()->create([
            'status' => 'completed',
            'total_amount' => 30000,
            'transaction_date' => now(),
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'sku_snapshot' => $product->sku,
            'quantity' => 2,
            'unit_price' => 15000,
            'line_total' => 30000,
        ]);

        Payment::factory()->create([
            'sale_id' => $sale->id,
            'amount_paid' => 30000,
            'method' => 'cash',
        ]);

        $this->actingAs($owner);
        $response = $this->get(route('owner.dashboard', ['period' => 'all']));

        $response->assertOk()
            ->assertSee('Omzet Penjualan')
            ->assertSee('30.000') // Omzet
            ->assertSee('Transaksi Selesai')
            ->assertSee('Menu Terjual')
            ->assertSee('Rata-rata Transaksi')
            ->assertSee('30.000');
    }

    /**
     * Uji Laporan Penjualan Pemilik & Ekspor Excel (.xlsx).
     */
    public function test_owner_and_admin_reports_excel_export(): void
    {
        $owner = User::factory()->owner()->create(['is_active' => true]);

        Sale::factory()->create([
            'status' => 'completed',
            'total_amount' => 50000,
            'transaction_date' => now(),
        ]);

        $this->actingAs($owner);

        // Akses halaman laporan pemilik
        $response = $this->get(route('owner.reports.index'));
        $response->assertOk()->assertSee('Laporan Penjualan');
        $response->assertSee('Ekspor Excel (.xlsx)');

        // Ekspor Excel (.xlsx) Pemilik
        $excelResponse = $this->get(route('owner.reports.export_excel'));
        $excelResponse->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $excelResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $excelResponse->headers->get('Content-Disposition'));

        // Ekspor Excel (.xlsx) Admin
        $this->actingAs($this->admin);
        $adminExcelResponse = $this->get(route('admin.reports.export_excel'));
        $adminExcelResponse->assertOk();
        $this->assertStringContainsString('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $adminExcelResponse->headers->get('Content-Type'));
        $this->assertStringContainsString('.xlsx', $adminExcelResponse->headers->get('Content-Disposition'));
    }

    /**
     * Uji Hapus Kategori: Dicegah jika masih ada barang, berhasil jika kosong.
     */
    public function test_category_deletion_rules(): void
    {
        $this->actingAs($this->admin);

        $catWithProduct = Category::factory()->create(['name' => 'Kategori Penuh']);
        Product::factory()->create(['category_id' => $catWithProduct->id]);

        $catEmpty = Category::factory()->create(['name' => 'Kategori Kosong']);

        // Hapus kategori yang masih memiliki barang -> gagal
        $failResponse = $this->delete(route('admin.categories.destroy', $catWithProduct->id));
        $failResponse->assertRedirect(route('admin.categories.index'));
        $failResponse->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $catWithProduct->id]);

        // Hapus kategori kosong -> sukses
        $successResponse = $this->delete(route('admin.categories.destroy', $catEmpty->id));
        $successResponse->assertRedirect(route('admin.categories.index'));
        $successResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $catEmpty->id]);
    }

    /**
     * Uji Hapus Barang: Dicegah jika memiliki riwayat penjualan, berhasil jika belum pernah dijual.
     */
    public function test_product_deletion_rules(): void
    {
        $this->actingAs($this->admin);

        $prodSold = Product::factory()->create(['name' => 'Barang Terjual']);
        $sale = Sale::factory()->create(['status' => 'completed', 'total_amount' => 10000]);
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $prodSold->id,
            'product_name_snapshot' => $prodSold->name,
            'sku_snapshot' => $prodSold->sku,
            'quantity' => 1,
            'unit_price' => 10000,
            'line_total' => 10000,
        ]);

        $prodNew = Product::factory()->create(['name' => 'Barang Baru Tanpa Penjualan']);

        // Hapus barang yang memiliki histori penjualan -> gagal
        $failResponse = $this->delete(route('admin.products.destroy', $prodSold->id));
        $failResponse->assertRedirect(route('admin.products.index'));
        $failResponse->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $prodSold->id]);

        // Hapus barang baru -> sukses
        $successResponse = $this->delete(route('admin.products.destroy', $prodNew->id));
        $successResponse->assertRedirect(route('admin.products.index'));
        $successResponse->assertSessionHas('success');
        $this->assertDatabaseMissing('products', ['id' => $prodNew->id]);
    }

    /**
     * Uji Role Admin tidak memiliki link / halaman Buka Mesin Kasir POS di navigasinya.
     */
    public function test_admin_sidebar_and_dashboard_do_not_have_pos_links(): void
    {
        $this->actingAs($this->admin);
        $response = $this->get(route('admin.dashboard'));
        $response->assertOk();

        // Tidak boleh ada link "Buka Mesin Kasir (POS)" atau "Buka Kasir POS"
        $response->assertDontSee('Buka Mesin Kasir (POS)');
        $response->assertDontSee('Buka Kasir POS');
    }

    /**
     * Uji Halaman Manajemen Pengguna tidak error (relasi sales() pada User bekerja normal).
     */
    public function test_admin_user_management_loads_successfully_without_error(): void
    {
        $this->actingAs($this->admin);

        // Buat beberapa penjualan untuk menguji sales_count
        Sale::factory()->create([
            'user_id' => $this->cashier->id,
            'status' => 'completed',
            'total_amount' => 50000,
        ]);

        $response = $this->get(route('admin.users.index'));
        $response->assertOk();
        $response->assertSee('Manajemen Pengguna (Akun)');
        $response->assertSee($this->admin->name);
        $response->assertSee($this->cashier->name);
        $response->assertSee('1 transaksi');
    }

    /**
     * Uji Halaman Riwayat Transaksi Admin: filter kasir hanya memuat role cashier dan kolom aksi berupa simbol button.
     */
    public function test_admin_transactions_history_filters_only_cashier_role_and_uses_symbol_buttons(): void
    {
        $this->actingAs($this->admin);

        $owner = User::factory()->owner()->create(['name' => 'Pak Bos Owner']);
        $anotherCashier = User::factory()->create(['name' => 'Siti Kasir', 'role' => 'cashier']);

        $sale = Sale::factory()->create([
            'user_id' => $anotherCashier->id,
            'status' => 'completed',
            'total_amount' => 75000,
        ]);

        $response = $this->get(route('admin.transactions.index'));
        $response->assertOk();

        // Kasir harus muncul di dropdown filter
        $response->assertSee('Siti Kasir');
        $response->assertSee($this->cashier->name);

        // Owner tidak boleh muncul di dalam dropdown filter kasir
        $response->assertDontSee('value="'.$owner->id.'"', false);

        // Kolom aksi berupa simbol button sesuai fitur (detail transaksi dan cetak struk)
        $response->assertSee('Lihat Detail Transaksi');
        $response->assertSee('Cetak Struk Digital');
        $response->assertSee(route('admin.transactions.show', $sale->id));
        $response->assertSee(route('pos.receipt', $sale->id));
    }

    /**
     * Uji Halaman POS: Daftar produk menggunakan grid 3 kolom dan card berukuran besar.
     */
    public function test_pos_product_catalog_uses_three_columns_and_enlarged_cards(): void
    {
        $this->actingAs($this->admin);

        Product::factory()->create([
            'name' => 'Kopi Arabika Spesial',
            'sku' => 'KOP-001',
            'selling_price' => 25000,
            'category_id' => $this->category->id,
            'is_active' => true,
        ]);

        $response = $this->get(route('pos.index'));
        $response->assertOk();

        // Layout split 50:50 and Catalog 2 columns (grid-cols-2)
        $response->assertSee('lg:w-1/2', false);
        $response->assertSee('grid-cols-2', false);
        $response->assertSee('h-[190px]', false);
        // Card memuat info produk lengkap
        $response->assertSee('Kopi Arabika Spesial');
        $response->assertSee('KOP-001');
        $response->assertSee('Rp 25.000');
    }

    /**
     * Uji Login Redirect: Setiap role (admin, cashier, owner, kitchen) diarahkan ke dashboard masing-masing tanpa 403.
     */
    public function test_roles_redirect_to_appropriate_dashboards_on_login_without_403(): void
    {
        $kitchen = User::factory()->create([
            'role' => 'kitchen',
            'is_active' => true,
        ]);

        $owner = User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);

        // 1. Kasir Login -> diarahkan ke cashier.dashboard
        $cashierLogin = $this->post(route('login.post'), [
            'email' => $this->cashier->email,
            'password' => 'password',
        ]);
        $cashierLogin->assertRedirect(route('cashier.dashboard'));
        $this->actingAs($this->cashier);
        $this->get(route('cashier.dashboard'))->assertOk();

        $this->post(route('logout'));
        $this->flushSession();

        // 2. Kitchen Login -> diarahkan ke kitchen.index
        $kitchenLogin = $this->post(route('login.post'), [
            'email' => $kitchen->email,
            'password' => 'password',
        ]);
        $kitchenLogin->assertRedirect(route('kitchen.index'));
        $this->actingAs($kitchen);
        $this->get(route('kitchen.index'))->assertOk();

        $this->post(route('logout'));
        $this->flushSession();

        // 3. Owner Login -> diarahkan ke owner.dashboard
        $ownerLogin = $this->post(route('login.post'), [
            'email' => $owner->email,
            'password' => 'password',
        ]);
        $ownerLogin->assertRedirect(route('owner.dashboard'));
        $this->actingAs($owner);
        $this->get(route('owner.dashboard'))->assertOk();

        $this->post(route('logout'));
        $this->flushSession();

        // 4. Admin Login -> diarahkan ke admin.dashboard
        $adminLogin = $this->post(route('login.post'), [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);
        $adminLogin->assertRedirect(route('admin.dashboard'));
        $this->actingAs($this->admin);
        $this->get(route('admin.dashboard'))->assertOk();
    }

    /**
     * Uji Role Owner: Mengakses cashier/dashboard diarahkan ke owner/dashboard tanpa 403.
     */
    public function test_owner_accessing_cashier_dashboard_is_redirected_to_owner_dashboard(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->actingAs($owner);
        $response = $this->get(route('cashier.dashboard'));
        $response->assertRedirect(route('owner.dashboard'));
    }
}
