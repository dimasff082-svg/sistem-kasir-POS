<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with('category')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $products = $query->paginate(10)->withQueryString();
        $categories = Category::active()->orderBy('name')->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create(): View
    {
        $categories = Category::active()->orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
        ], [
            'sku.required' => 'Kode barang / SKU wajib diisi.',
            'sku.unique' => 'Kode barang / SKU sudah terdaftar. Gunakan kode yang unik.',
            'name.required' => 'Nama barang wajib diisi.',
            'purchase_price.min' => 'Harga beli tidak boleh negatif.',
            'selling_price.min' => 'Harga jual tidak boleh negatif.',
            'stock.min' => 'Stok awal tidak boleh bernilai negatif.',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $product = Product::create([
                'category_id' => $validated['category_id'] ?? null,
                'sku' => strtoupper(trim($validated['sku'])),
                'name' => $validated['name'],
                'unit' => $validated['unit'],
                'purchase_price' => $validated['purchase_price'],
                'selling_price' => $validated['selling_price'],
                'stock' => $validated['stock'],
                'minimum_stock' => $validated['minimum_stock'],
                'is_active' => true,
            ]);

            if ($product->stock > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'movement_type' => 'initial',
                    'quantity_change' => $product->stock,
                    'stock_before' => 0,
                    'stock_after' => $product->stock,
                    'reason' => 'Stok awal saat barang didaftarkan',
                ]);
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Barang baru berhasil ditambahkan.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::active()->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'category_id' => ['nullable', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku,'.$product->id],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:50'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
        ], [
            'sku.required' => 'Kode barang / SKU wajib diisi.',
            'sku.unique' => 'Kode barang / SKU sudah terdaftar.',
            'name.required' => 'Nama barang wajib diisi.',
            'purchase_price.min' => 'Harga beli tidak boleh bernilai negatif.',
            'selling_price.min' => 'Harga jual tidak boleh bernilai negatif.',
        ]);

        $product->update([
            'category_id' => $validated['category_id'] ?? null,
            'sku' => strtoupper(trim($validated['sku'])),
            'name' => $validated['name'],
            'unit' => $validated['unit'],
            'purchase_price' => $validated['purchase_price'],
            'selling_price' => $validated['selling_price'],
            'minimum_stock' => $validated['minimum_stock'],
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Informasi barang berhasil diperbarui.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        $status = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.products.index')->with('success', "Barang {$product->name} berhasil {$status}.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $salesCount = $product->saleItems()->count();

        if ($salesCount > 0) {
            return redirect()->route('admin.products.index')->with('error', "Barang '{$product->name}' tidak dapat dihapus karena memiliki riwayat {$salesCount} transaksi penjualan. Anda dapat menonaktifkan status barang agar tidak muncul di kasir.");
        }

        DB::transaction(function () use ($product) {
            $product->stockMovements()->delete();
            $product->delete();
        });

        return redirect()->route('admin.products.index')->with('success', "Barang '{$product->name}' berhasil dihapus.");
    }
}
