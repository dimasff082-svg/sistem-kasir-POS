<?php

namespace Database\Seeders;

use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;

class RestaurantTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tables = [
            ['table_number' => 'M001', 'name' => 'Meja 1 (Depan)'],
            ['table_number' => 'M002', 'name' => 'Meja 2 (Tengah)'],
            ['table_number' => 'M003', 'name' => 'Meja 3 (Tengah)'],
            ['table_number' => 'M004', 'name' => 'Meja 4 (Jendela)'],
            ['table_number' => 'M005', 'name' => 'Meja 5 (Outdoor)'],
            ['table_number' => 'M006', 'name' => 'Meja 6 (Outdoor)'],
            ['table_number' => 'M007', 'name' => 'Meja 7 (VIP)'],
            ['table_number' => 'M008', 'name' => 'Meja 8 (VIP)'],
        ];

        foreach ($tables as $t) {
            RestaurantTable::firstOrCreate(
                ['table_number' => $t['table_number']],
                [
                    'name' => $t['name'],
                    'status' => 'available',
                ]
            );
        }

        // Tambah akun Kitchen/Dapur jika belum ada
        User::firstOrCreate(
            ['email' => 'dapur@pos.test'],
            [
                'name' => 'Chef Dapur Utama',
                'password' => bcrypt('password'),
                'role' => 'kitchen',
                'is_active' => true,
            ]
        );

        // Update data penjualan lama agar statusnya valid
        Sale::whereNull('order_status')->update(['order_status' => 'completed']);
        Sale::whereNull('payment_status')->update(['payment_status' => 'paid']);
        Sale::whereNull('table_number')->update(['table_number' => 'M001']);
    }
}
