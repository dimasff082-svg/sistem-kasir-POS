<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::with('category')->orderBy('stock');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->input('filter') === 'low_stock') {
            $query->whereColumn('stock', '<=', 'minimum_stock');
        } elseif ($request->input('filter') === 'out_of_stock') {
            $query->where('stock', '<=', 0);
        }

        $products = $query->paginate(10)->withQueryString();

        return view('admin.stock.index', compact('products'));
    }

    public function adjustmentForm(Product $product): View
    {
        return view('admin.stock.adjust', compact('product'));
    }

    public function adjust(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'adjustment_type' => ['required', 'in:add,subtract,set'],
            'amount' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'max:255'],
        ], [
            'amount.required' => 'Jumlah penyesuaian wajib diisi.',
            'amount.min' => 'Jumlah tidak boleh bernilai negatif.',
            'reason.required' => 'Alasan penyesuaian stok wajib diisi untuk kebutuhan audit.',
        ]);

        $error = null;

        DB::transaction(function () use ($validated, $product, $request, &$error) {
            $lockedProduct = Product::lockForUpdate()->findOrFail($product->id);
            $stockBefore = $lockedProduct->stock;
            $stockAfter = $stockBefore;
            $quantityChange = 0;
            $movementType = 'adjustment';

            if ($validated['adjustment_type'] === 'add') {
                $quantityChange = (int) $validated['amount'];
                $stockAfter = $stockBefore + $quantityChange;
                $movementType = 'purchase_in';
            } elseif ($validated['adjustment_type'] === 'subtract') {
                $quantityChange = -((int) $validated['amount']);
                $stockAfter = $stockBefore + $quantityChange;
                $movementType = 'damaged';
            } elseif ($validated['adjustment_type'] === 'set') {
                $targetStock = (int) $validated['amount'];
                $quantityChange = $targetStock - $stockBefore;
                $stockAfter = $targetStock;
                $movementType = 'adjustment';
            }

            if ($stockAfter < 0) {
                $error = 'Penyesuaian ditolak: Stok akhir tidak boleh bernilai negatif (kurang dari nol).';

                return;
            }

            $lockedProduct->update(['stock' => $stockAfter]);

            StockMovement::create([
                'product_id' => $lockedProduct->id,
                'user_id' => $request->user()->id,
                'movement_type' => $movementType,
                'quantity_change' => $quantityChange,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'reason' => $validated['reason'],
            ]);
        });

        if ($error) {
            return back()->with('error', $error);
        }

        return redirect()->route('admin.stock.index')->with('success', "Penyesuaian stok untuk {$product->name} berhasil dicatat.");
    }

    public function movements(Request $request): View
    {
        $query = StockMovement::with(['product', 'user'])->latest();

        if ($search = $request->input('search')) {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->input('movement_type'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $movements = $query->paginate(15)->withQueryString();

        return view('admin.stock.movements', compact('movements'));
    }
}
