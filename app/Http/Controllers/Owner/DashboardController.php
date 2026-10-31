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

        // 1 & 2. Omzet (Total Revenue) & Total Struk
        $summary = (clone $salesQuery)
            ->selectRaw('SUM(total_amount) as total_omzet, COUNT(*) as total_struk')
            ->first();
        $omzet = (float) ($summary->total_omzet ?? 0);
        $totalStruk = (int) ($summary->total_struk ?? 0);

        $totalItemsSold = (clone $itemsQuery)->sum('quantity');

        // 3. Rata-rata Nilai Transaksi (AOV)
        $rataRataTransaksi = $totalStruk > 0 ? round($omzet / $totalStruk) : 0;

        // All-Time Totals for high-level comparison (1 single query)
        $allTimeSummary = Sale::where('status', 'completed')
            ->selectRaw('SUM(total_amount) as all_time_omzet, COUNT(*) as all_time_struk')
            ->first();
        $omzetAllTime = (float) ($allTimeSummary->all_time_omzet ?? 0);
        $totalStrukAllTime = (int) ($allTimeSummary->all_time_struk ?? 0);

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
