<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Sale::with(['payment', 'items'])
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($search = $request->input('search')) {
            $query->where('transaction_number', 'like', "%{$search}%");
        }

        if ($request->filled('date_from')) {
            $query->whereDate('transaction_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('transaction_date', '<=', $request->input('date_to'));
        }

        $sales = $query->paginate(15)->withQueryString();

        return view('cashier.transactions.index', compact('sales'));
    }

    public function show(Request $request, Sale $sale): View
    {
        // Kasir hanya boleh membuka transaksi yang dilayaninya sendiri
        if ($sale->user_id !== $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'Anda tidak memiliki hak untuk melihat transaksi ini.');
        }

        $sale->load(['user', 'payment', 'items.product']);

        return view('cashier.transactions.show', compact('sale'));
    }
}
