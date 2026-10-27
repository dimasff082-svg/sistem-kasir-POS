<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        // Period filter (default: this month)
        $period = $request->input('period', 'month');

        $startDate = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            'all' => null,
            default => now()->startOfMonth(),
        };

        // Base Sales Query for the selected period
        $salesQuery = Sale::where('status', 'completed');
        $itemsQuery = SaleItem::whereHas('sale', fn ($q) => $q->where('status', 'completed'));

        if ($startDate) {
            $salesQuery->where('transaction_date', '>=', $startDate);
            $itemsQuery->whereHas('sale', fn ($q) => $q->where('status', 'completed')->where('transaction_date', '>=', $startDate));
        }

        // 1. Omzet (Total Revenue)
        $omzet = (clone $salesQuery)->sum('total_amount');

        // 2. HPP (Harga Pokok Penjualan)
        $hpp = (clone $itemsQuery)->sum(DB::raw('unit_purchase_price * quantity'));

        // 3. Laba Kotor (Gross Profit = Omzet - HPP)
        $labaKotor = $omzet - $hpp;

        // 4. Nilai Aset Stok (Total Value of Current Inventory at purchase cost)
        $nilaiAsetStok = Product::where('is_active', true)->sum(DB::raw('purchase_price * stock'));

        // Additional Contextual Analytics for Owner
        $potensiNilaiJualStok = Product::where('is_active', true)->sum(DB::raw('selling_price * stock'));
        $totalStruk = (clone $salesQuery)->count();
        $totalItemsSold = (clone $itemsQuery)->sum('quantity');

        // All-Time Totals for high-level comparison
        $omzetAllTime = Sale::where('status', 'completed')->sum('total_amount');
        $hppAllTime = SaleItem::whereHas('sale', fn ($q) => $q->where('status', 'completed'))->sum(DB::raw('unit_purchase_price * quantity'));
        $labaKotorAllTime = $omzetAllTime - $hppAllTime;

        // Recent completed transactions
        $recentSales = Sale::with(['user', 'payment'])
            ->where('status', 'completed')
            ->latest('transaction_date')
            ->take(5)
            ->get();

        // Top 5 selling products in period
        $topProducts = (clone $itemsQuery)
            ->select(
                'product_name_snapshot',
                'sku_snapshot',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(line_total) as total_revenue'),
                DB::raw('SUM(line_total - (unit_purchase_price * quantity)) as total_profit')
            )
            ->groupBy('product_name_snapshot', 'sku_snapshot')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();

        return view('owner.dashboard', compact(
            'period',
            'omzet',
            'hpp',
            'labaKotor',
            'nilaiAsetStok',
            'potensiNilaiJualStok',
            'totalStruk',
            'totalItemsSold',
            'omzetAllTime',
            'hppAllTime',
            'labaKotorAllTime',
            'recentSales',
            'topProducts'
        ));
    }
}
