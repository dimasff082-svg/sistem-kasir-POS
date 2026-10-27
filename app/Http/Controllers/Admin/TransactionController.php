<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Sale::with(['user', 'payment', 'items'])->latest();

        if ($search = $request->input('search')) {
            $query->where('transaction_number', 'like', "%{$search}%");
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->input('date_to'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('payment_method')) {
            $query->whereHas('payment', function ($q) use ($request) {
                $q->where('method', $request->input('payment_method'));
            });
        }

        $sales = $query->paginate(15)->withQueryString();
        $cashiers = User::where('role', 'cashier')->orderBy('name')->get();

        return view('admin.transactions.index', compact('sales', 'cashiers'));
    }

    public function show(Sale $sale): View
    {
        $sale->load(['user', 'payment', 'items.product']);

        return view('admin.transactions.show', compact('sale'));
    }
}
