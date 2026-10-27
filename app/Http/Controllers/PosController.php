<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PosController extends Controller
{
    public function index(): View
    {
        $categories = Category::active()->orderBy('name')->get();
        $products = Product::active()
            ->with('category')
            ->orderBy('name')
            ->get();

        return view('pos.index', compact('categories', 'products'));
    }

    public function search(Request $request): JsonResponse
    {
        $query = Product::active()->with('category');

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($catId = $request->input('category_id')) {
            $query->where('category_id', $catId);
        }

        $products = $query->orderBy('name')->limit(30)->get();

        return response()->json($products);
    }

    public function checkout(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'items.required' => 'Keranjang belanja tidak boleh kosong.',
            'items.min' => 'Pilih minimal satu barang untuk bertransaksi.',
            'payment_method.required' => 'Pilih metode pembayaran.',
            'amount_tendered.min' => 'Jumlah uang yang diterima tidak valid.',
            'discount_amount.min' => 'Nilai diskon tidak valid.',
        ]);

        try {
            $sale = DB::transaction(function () use ($validated, $request) {
                $itemIds = array_column($validated['items'], 'id');

                // Row-lock products to prevent race conditions during concurrent checkouts
                $products = Product::whereIn('id', $itemIds)->lockForUpdate()->get()->keyBy('id');

                $subtotal = 0;
                $processedItems = [];

                foreach ($validated['items'] as $item) {
                    $productId = $item['id'];
                    $qty = (int) $item['quantity'];

                    if (! isset($products[$productId])) {
                        throw new \Exception("Barang dengan ID {$productId} tidak ditemukan.");
                    }

                    $product = $products[$productId];

                    if (! $product->is_active) {
                        throw new \Exception("Barang '{$product->name}' sedang tidak aktif dan tidak dapat dijual.");
                    }

                    if ($product->stock < $qty) {
                        throw new \Exception("Stok untuk '{$product->name}' tidak mencukupi. Tersedia: {$product->stock}, diminta: {$qty}.");
                    }

                    $lineTotal = $qty * (float) $product->selling_price;
                    $subtotal += $lineTotal;

                    $processedItems[] = [
                        'product' => $product,
                        'quantity' => $qty,
                        'unit_price' => (float) $product->selling_price,
                        'unit_purchase_price' => (float) $product->purchase_price,
                        'line_total' => $lineTotal,
                    ];
                }

                $discountAmount = min($subtotal, max(0, (float) ($validated['discount_amount'] ?? 0)));
                $totalAmount = max(0, $subtotal - $discountAmount);
                $method = $validated['payment_method'];
                $amountTendered = (float) ($validated['amount_tendered'] ?? $totalAmount);

                if ($method === 'cash') {
                    if ($amountTendered < $totalAmount) {
                        throw new \Exception('Uang pembayaran tunai kurang. Total tagihan: Rp '.number_format($totalAmount, 0, ',', '.').', uang diterima: Rp '.number_format($amountTendered, 0, ',', '.').'.');
                    }
                    $changeAmount = $amountTendered - $totalAmount;
                } else {
                    $amountTendered = $totalAmount;
                    $changeAmount = 0;
                }

                // Generate Unique Transaction Number: TRX-YYYYMMDD-XXXX
                $trxNumber = 'TRX-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
                while (Sale::where('transaction_number', $trxNumber)->exists()) {
                    $trxNumber = 'TRX-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
                }

                // 1. Simpan Transaksi Utama (Sale)
                $sale = Sale::create([
                    'transaction_number' => $trxNumber,
                    'user_id' => $request->user()->id,
                    'transaction_date' => now(),
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'status' => 'completed',
                    'notes' => $validated['notes'] ?? null,
                ]);

                // 2. Simpan Sale Items & Update Stock & Catat Pergerakan Stok
                foreach ($processedItems as $pItem) {
                    $prod = $pItem['product'];
                    $qty = $pItem['quantity'];

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'product_id' => $prod->id,
                        'product_name_snapshot' => $prod->name,
                        'sku_snapshot' => $prod->sku,
                        'quantity' => $qty,
                        'unit_price' => $pItem['unit_price'],
                        'unit_purchase_price' => $pItem['unit_purchase_price'],
                        'discount_amount' => 0,
                        'line_total' => $pItem['line_total'],
                    ]);

                    $stockBefore = $prod->stock;
                    $stockAfter = $stockBefore - $qty;

                    // Kurangi stok produk
                    $prod->update(['stock' => $stockAfter]);

                    // Catat mutasi stok
                    StockMovement::create([
                        'product_id' => $prod->id,
                        'user_id' => $request->user()->id,
                        'movement_type' => 'sale_out',
                        'quantity_change' => -$qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reference_type' => Sale::class,
                        'reference_id' => $sale->id,
                        'reason' => "Penjualan transaksi {$sale->transaction_number}",
                    ]);
                }

                // 3. Simpan Catatan Pembayaran
                Payment::create([
                    'sale_id' => $sale->id,
                    'method' => $method,
                    'amount_tendered' => $amountTendered,
                    'amount_paid' => $totalAmount,
                    'change_amount' => $changeAmount,
                    'status' => 'paid',
                    'verified_by' => $request->user()->id,
                    'paid_at' => now(),
                ]);

                return $sale;
            });

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi berhasil diproses.',
                    'sale_id' => $sale->id,
                    'redirect_url' => route('pos.receipt', $sale->id),
                ]);
            }

            return redirect()->route('pos.receipt', $sale->id)->with('success', 'Transaksi berhasil diselesaikan.');
        } catch (\Exception $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function receipt(Sale $sale): View
    {
        $sale->load(['items.product', 'payment', 'user']);

        return view('pos.receipt', compact('sale'));
    }
}
