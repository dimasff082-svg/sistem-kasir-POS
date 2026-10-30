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

        $salesToday = Sale::where('status', 'completed')
            ->whereDate('transaction_date', $today)
            ->sum('total_amount');

        $trxTodayCount = Sale::where('status', 'completed')
            ->whereDate('transaction_date', $today)
            ->count();

        $activeProductsCount = Product::active()->count();

        $tablesCount = RestaurantTable::count();
        $occupiedTablesCount = RestaurantTable::where('status', 'occupied')->count();

        $recentSales = Sale::with(['user', 'payment'])
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
