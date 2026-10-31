<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
            'description' => ['nullable', 'string', 'max:1000'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'sku.required' => 'Kode menu wajib diisi.',
            'sku.unique' => 'Kode menu sudah terdaftar. Gunakan kode yang unik.',
            'name.required' => 'Nama menu wajib diisi.',
            'selling_price.required' => 'Harga menu wajib diisi.',
            'selling_price.min' => 'Harga menu tidak boleh negatif.',
            'image.image' => 'File foto menu harus berupa gambar.',
            'image.mimes' => 'Format foto menu yang didukung adalah JPEG, PNG, JPG, dan WEBP.',
            'image.max' => 'Ukuran foto menu maksimal adalah 2MB.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        Product::create([
            'category_id' => $validated['category_id'] ?? null,
            'sku' => strtoupper(trim($validated['sku'])),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'unit' => 'porsi',
            'selling_price' => $validated['selling_price'],
            'image' => $imagePath,
            'is_active' => true,
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Menu baru berhasil ditambahkan.');
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
            'description' => ['nullable', 'string', 'max:1000'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'sku.required' => 'Kode menu wajib diisi.',
            'sku.unique' => 'Kode menu sudah terdaftar.',
            'name.required' => 'Nama menu wajib diisi.',
            'selling_price.required' => 'Harga menu wajib diisi.',
            'selling_price.min' => 'Harga menu tidak boleh bernilai negatif.',
            'image.image' => 'File foto menu harus berupa gambar.',
            'image.mimes' => 'Format foto menu yang didukung adalah JPEG, PNG, JPG, dan WEBP.',
            'image.max' => 'Ukuran foto menu maksimal adalah 2MB.',
        ]);

        $data = [
            'category_id' => $validated['category_id'] ?? null,
            'sku' => strtoupper(trim($validated['sku'])),
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'selling_price' => $validated['selling_price'],
        ];

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('admin.products.index')->with('success', 'Informasi menu berhasil diperbarui.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product->update(['is_active' => ! $product->is_active]);

        $status = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.products.index')->with('success', "Menu {$product->name} berhasil {$status}.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        $salesCount = $product->saleItems()->count();

        if ($salesCount > 0) {
            return redirect()->route('admin.products.index')->with('error', "Menu '{$product->name}' tidak dapat dihapus karena memiliki riwayat {$salesCount} transaksi pesanan. Anda dapat menonaktifkan status menu agar tidak tampil di daftar pemesanan.");
        }

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', "Menu '{$product->name}' berhasil dihapus.");
    }
}
