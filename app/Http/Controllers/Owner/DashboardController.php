<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
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

        // 2. Total Struk & Menu Terjual
        $totalStruk = (clone $salesQuery)->count();
        $totalItemsSold = (clone $itemsQuery)->sum('quantity');

        // 3. Rata-rata Nilai Transaksi (AOV)
        $rataRataTransaksi = $totalStruk > 0 ? round($omzet / $totalStruk) : 0;

        // All-Time Totals for high-level comparison
        $omzetAllTime = Sale::where('status', 'completed')->sum('total_amount');
        $totalStrukAllTime = Sale::where('status', 'completed')->count();

        // Recent completed transactions
        $recentSales = Sale::with(['user', 'confirmer', 'payment'])
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
                DB::raw('SUM(line_total) as total_revenue')
            )
            ->groupBy('product_name_snapshot', 'sku_snapshot')
            ->orderByDesc('total_revenue')
            ->take(5)
            ->get();

        return view('owner.dashboard', compact(
            'period',
            'omzet',
            'totalStruk',
            'totalItemsSold',
            'rataRataTransaksi',
            'omzetAllTime',
            'totalStrukAllTime',
            'recentSales',
            'topProducts'
        ));
    }
}
