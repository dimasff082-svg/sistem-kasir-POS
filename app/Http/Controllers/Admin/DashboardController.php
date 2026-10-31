<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = now()->toDateString();

        $todaySummary = Sale::where('status', 'completed')
            ->whereDate('transaction_date', $today)
            ->selectRaw('SUM(total_amount) as total_sales, COUNT(*) as total_trx')
            ->first();

        $salesToday = (float) ($todaySummary->total_sales ?? 0);
        $trxTodayCount = (int) ($todaySummary->total_trx ?? 0);

        $activeProductsCount = Product::active()->count();

        $tableStats = RestaurantTable::selectRaw("COUNT(*) as total_tables, SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_tables")->first();
        $tablesCount = (int) ($tableStats->total_tables ?? 0);
        $occupiedTablesCount = (int) ($tableStats->occupied_tables ?? 0);

        $recentSales = Sale::with(['user', 'confirmer', 'payment'])
            ->latest()
            ->limit(6)
            ->get();

        $restaurantTables = RestaurantTable::with('activeOpenBill')
            ->orderBy('table_number')
            ->limit(8)
            ->get();

        $topProducts = SaleItem::select(
            'product_name_snapshot',
            'sku_snapshot',
            DB::raw('SUM(quantity) as total_sold'),
            DB::raw('SUM(line_total) as total_revenue')
        )
            ->whereHas('sale', fn ($q) => $q->where('status', 'completed'))
            ->groupBy('product_name_snapshot', 'sku_snapshot')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'salesToday',
            'trxTodayCount',
            'activeProductsCount',
            'tablesCount',
            'occupiedTablesCount',
            'recentSales',
            'restaurantTables',
            'topProducts'
        ));
    }
}
