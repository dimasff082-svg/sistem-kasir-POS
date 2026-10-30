<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OpenBill;
use App\Models\RestaurantTable;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantTableController extends Controller
{
    /**
     * Tampilan daftar seluruh data meja restoran.
     */
    public function index(Request $request): View
    {
        $query = RestaurantTable::withCount(['orders', 'activeOrders', 'openBills'])
            ->with(['activeOpenBill'])
            ->orderBy('table_number');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('table_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        $tables = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => RestaurantTable::count(),
            'available' => RestaurantTable::where('status', 'available')->count(),
            'occupied' => RestaurantTable::where('status', 'occupied')->count(),
        ];

        return view('admin.tables.index', compact('tables', 'stats'));
    }

    /**
     * Menyimpan data meja baru.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:20', 'unique:restaurant_tables,table_number'],
            'name' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:available,occupied'],
        ], [
            'table_number.required' => 'Nomor/Kode Meja wajib diisi (misal: M001).',
            'table_number.unique' => 'Nomor/Kode Meja tersebut sudah digunakan.',
            'name.required' => 'Nama atau keterangan meja wajib diisi.',
        ]);

        $tableNumber = strtoupper(trim($validated['table_number']));

        RestaurantTable::create([
            'table_number' => $tableNumber,
            'name' => trim($validated['name']),
            'status' => $validated['status'] ?? 'available',
        ]);

        return redirect()->route('admin.tables.index')
            ->with('success', "Meja {$tableNumber} berhasil ditambahkan.");
    }

    /**
     * Memperbarui data meja.
     */
    public function update(Request $request, RestaurantTable $table): RedirectResponse
    {
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:20', 'unique:restaurant_tables,table_number,'.$table->id],
            'name' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'in:available,occupied'],
        ], [
            'table_number.required' => 'Nomor/Kode Meja wajib diisi.',
            'table_number.unique' => 'Nomor/Kode Meja tersebut sudah digunakan meja lain.',
            'name.required' => 'Nama meja wajib diisi.',
        ]);

        $newTableNumber = strtoupper(trim($validated['table_number']));
        $oldTableNumber = $table->table_number;

        // Jika nomor meja diubah, perbarui relasi pada sales dan open_bills
        if ($newTableNumber !== $oldTableNumber) {
            Sale::where('table_number', $oldTableNumber)->update(['table_number' => $newTableNumber]);
            OpenBill::where('table_number', $oldTableNumber)->update(['table_number' => $newTableNumber]);
        }

        $table->update([
            'table_number' => $newTableNumber,
            'name' => trim($validated['name']),
            'status' => $validated['status'],
        ]);

        return redirect()->route('admin.tables.index')
            ->with('success', "Data Meja {$newTableNumber} berhasil diperbarui.");
    }

    /**
     * Menghapus meja dari sistem.
     */
    public function destroy(RestaurantTable $table): RedirectResponse
    {
        // Cek apakah meja memiliki pesanan yang belum dibayar
        $hasUnpaidOrders = Sale::where('table_number', $table->table_number)
            ->where('payment_status', 'unpaid')
            ->exists();

        if ($hasUnpaidOrders) {
            return back()->with('error', "Meja {$table->table_number} tidak dapat dihapus karena masih memiliki pesanan aktif yang belum dibayar.");
        }

        // Cek apakah meja memiliki sesi Open Bill yang masih aktif
        $hasActiveBill = OpenBill::where('table_number', $table->table_number)
            ->where('status', 'open')
            ->exists();

        if ($hasActiveBill) {
            return back()->with('error', "Meja {$table->table_number} tidak dapat dihapus karena sesi Open Bill masih aktif.");
        }

        $tableNumber = $table->table_number;
        $table->delete();

        return redirect()->route('admin.tables.index')
            ->with('success', "Meja {$tableNumber} berhasil dihapus dari sistem.");
    }

    /**
     * Halaman cetak barcode / QR Code khusus untuk meja tertentu.
     * Ketika barcode/QR code ini discan pelanggan, langsung diarahkan ke URL pemesanan menu meja tersebut.
     */
    public function printQr(RestaurantTable $table): View
    {
        $orderUrl = route('order.index', $table->table_number);

        return view('admin.tables.print_qr', compact('table', 'orderUrl'));
    }
}
