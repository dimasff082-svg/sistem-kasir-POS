<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $today = now()->toDateString();

        $salesToday = Sale::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereDate('transaction_date', $today)
            ->sum('total_amount');

        $trxTodayCount = Sale::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereDate('transaction_date', $today)
            ->count();

        $recentSales = Sale::with(['payment', 'items'])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('cashier.dashboard', compact(
            'salesToday',
            'trxTodayCount',
            'recentSales'
        ));
    }
}
