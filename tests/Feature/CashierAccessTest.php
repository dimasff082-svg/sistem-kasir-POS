<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;

    protected User $admin;

    protected RestaurantTable $table;

    protected Product $product;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cashier = User::factory()->create([
            'role' => 'cashier',
            'is_active' => true,
        ]);
        $this->admin = User::factory()->admin()->create([
            'is_active' => true,
        ]);
        $this->category = Category::factory()->create(['is_active' => true]);
        $this->table = RestaurantTable::create([
            'table_number' => 'M001',
            'name' => 'Meja 1',
            'capacity' => 4,
            'status' => 'available',
        ]);
        $this->product = Product::factory()->create([
            'category_id' => $this->category->id,
            'selling_price' => 25000,
            'is_active' => true,
        ]);
    }

    public function test_cashier_can_access_all_cashier_pages(): void
    {
        $pages = [
            route('takeaway.index') => 200,
            route('cashier.dashboard') => 200,
            route('cashier.open_bills.index') => 200,
            route('cashier.transactions.index') => 200,
        ];

        foreach ($pages as $url => $expectedStatus) {
            $response = $this->actingAs($this->cashier)->get($url);
            $this->assertEquals($expectedStatus, $response->status(), "Kasir gagal mengakses URL: {$url} dengan status code: ".$response->status());
        }
    }

    public function test_pos_redirects_cashier_to_cashier_dashboard(): void
    {
        $response = $this->actingAs($this->cashier)->get(route('pos.index'));
        $response->assertRedirect(route('cashier.dashboard'));
    }

    public function test_cashier_cannot_access_admin_exclusive_routes(): void
    {
        $resDashboard = $this->actingAs($this->cashier)->get(route('admin.dashboard'));
        $resDashboard->assertForbidden();

        $resCategories = $this->actingAs($this->cashier)->get(route('admin.categories.index'));
        $resCategories->assertForbidden();

        $resUsers = $this->actingAs($this->cashier)->get(route('admin.users.index'));
        $resUsers->assertForbidden();
    }

    public function test_cashier_can_confirm_payment_with_formatted_thousand_dots(): void
    {
        $sale = Sale::create([
            'transaction_number' => 'ORD-TEST01',
            'table_number' => 'M001',
            'customer_name' => 'Budi Santoso',
            'transaction_date' => now(),
            'subtotal' => 50000,
            'total_amount' => 50000,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'order_status' => 'pending',
        ]);

        // Kirim nominal dengan titik ribuan (misal "60.000")
        $response = $this->actingAs($this->cashier)->post(route('cashier.orders.confirm_payment', $sale->id), [
            'payment_method' => 'cash',
            'amount_tendered' => '60.000',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $sale->refresh();
        $this->assertEquals('paid', $sale->payment_status);
        $this->assertEquals($this->cashier->id, $sale->confirmed_by);
        $this->assertNotNull($sale->payment);
        $this->assertEquals(60000, (float) $sale->payment->amount_tendered);
        $this->assertEquals(10000, (float) $sale->payment->change_amount);
    }
}
