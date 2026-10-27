<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@pos.test'],
            [
                'name' => 'Administrator Toko',
                'password' => bcrypt('password'),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'kasir@pos.test'],
            [
                'name' => 'Kasir Utama',
                'password' => bcrypt('password'),
                'role' => 'cashier',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'pemilik@pos.test'],
            [
                'name' => 'Pemilik Toko',
                'password' => bcrypt('password'),
                'role' => 'owner',
                'is_active' => true,
            ]
        );

        // Kategori Awal
        $catFood = Category::firstOrCreate(['name' => 'Makanan & Snack'], ['description' => 'Aneka makanan ringan dan biskuit', 'is_active' => true]);
        $catDrink = Category::firstOrCreate(['name' => 'Minuman Segar'], ['description' => 'Minuman kemasan botol dan dingin', 'is_active' => true]);
        $catAtk = Category::firstOrCreate(['name' => 'Alat Tulis Kantor'], ['description' => 'Peralatan kantor dan sekolah', 'is_active' => true]);

        // Produk Awal
        $initialProducts = [
            [
                'category_id' => $catDrink->id,
                'sku' => 'MNM-001',
                'name' => 'Air Mineral Aqua 600ml',
                'unit' => 'botol',
                'purchase_price' => 2500,
                'selling_price' => 3500,
                'stock' => 50,
                'minimum_stock' => 10,
            ],
            [
                'category_id' => $catDrink->id,
                'sku' => 'MNM-002',
                'name' => 'Teh Botol Sosro 450ml',
                'unit' => 'botol',
                'purchase_price' => 4000,
                'selling_price' => 6000,
                'stock' => 35,
                'minimum_stock' => 8,
            ],
            [
                'category_id' => $catFood->id,
                'sku' => 'MKN-001',
                'name' => 'Indomie Goreng Original',
                'unit' => 'bungkus',
                'purchase_price' => 2800,
                'selling_price' => 3500,
                'stock' => 80,
                'minimum_stock' => 15,
            ],
            [
                'category_id' => $catFood->id,
                'sku' => 'MKN-002',
                'name' => 'Chitato Sapi Panggang 68g',
                'unit' => 'bungkus',
                'purchase_price' => 8500,
                'selling_price' => 11000,
                'stock' => 25,
                'minimum_stock' => 5,
            ],
            [
                'category_id' => $catAtk->id,
                'sku' => 'ATK-001',
                'name' => 'Buku Tulis Sinar Dunia 38 Lembar',
                'unit' => 'buku',
                'purchase_price' => 3500,
                'selling_price' => 5000,
                'stock' => 4, // low stock for test
                'minimum_stock' => 10,
            ],
            [
                'category_id' => $catAtk->id,
                'sku' => 'ATK-002',
                'name' => 'Pulpen Standard AE7 Hitam',
                'unit' => 'pcs',
                'purchase_price' => 2000,
                'selling_price' => 3000,
                'stock' => 0, // out of stock for test
                'minimum_stock' => 5,
            ],
        ];

        foreach ($initialProducts as $p) {
            $product = Product::firstOrCreate(
                ['sku' => $p['sku']],
                [
                    'category_id' => $p['category_id'],
                    'name' => $p['name'],
                    'unit' => $p['unit'],
                    'purchase_price' => $p['purchase_price'],
                    'selling_price' => $p['selling_price'],
                    'stock' => $p['stock'],
                    'minimum_stock' => $p['minimum_stock'],
                    'is_active' => true,
                ]
            );

            if ($product->wasRecentlyCreated && $p['stock'] > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $admin->id,
                    'movement_type' => 'initial',
                    'quantity_change' => $p['stock'],
                    'stock_before' => 0,
                    'stock_after' => $p['stock'],
                    'reason' => 'Stok awal inisialisasi sistem',
                ]);
            }
        }
    }
}
