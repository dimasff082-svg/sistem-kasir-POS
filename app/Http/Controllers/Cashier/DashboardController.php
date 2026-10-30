<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\OpenBill;
use App\Models\Payment;
use App\Models\RestaurantTable;
use App\Models\Sale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Dashboard Kasir Sederhana sesuai spesifikasi revisi:
     * Tabel: ID Pesanan | Meja | Total | Pembayaran | Aksi
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Pemilik toko diarahkan ke Dashboard Pemilik
        if ($user->isOwner()) {
            return redirect()->route('owner.dashboard');
        }

        $query = Sale::with(['items.product', 'confirmer', 'openBill'])->latest();

        // Filter nomor pesanan / pencarian
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhere('table_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        // Filter status pembayaran (Belum Dibayar / Lunas)
        if ($paymentStatus = $request->input('payment_status')) {
            if ($paymentStatus !== 'all') {
                $query->where('payment_status', $paymentStatus);
            }
        }

        // Filter nomor meja
        if ($tableNumber = $request->input('table_number')) {
            if ($tableNumber !== 'all') {
                $query->where('table_number', $tableNumber);
            }
        }

        $sales = $query->paginate(15)->withQueryString();

        // Statistik hari ini untuk terminal kasir
        $today = now()->toDateString();
        $unpaidCount = Sale::where('payment_status', 'unpaid')
            ->whereDate('transaction_date', $today)
            ->count();

        $unpaidTotal = Sale::where('payment_status', 'unpaid')
            ->whereDate('transaction_date', $today)
            ->sum('total_amount');

        $paidCount = Sale::where('payment_status', 'paid')
            ->whereDate('transaction_date', $today)
            ->count();

        $paidTotal = Sale::where('payment_status', 'paid')
            ->whereDate('transaction_date', $today)
            ->sum('total_amount');

        $tables = RestaurantTable::orderBy('table_number')->get();
        $activeOpenBillsCount = OpenBill::where('status', 'open')->count();
        $pendingOpenBillRequests = OpenBill::where('status', 'requested')->latest()->get();

        return view('cashier.dashboard', compact(
            'sales',
            'unpaidCount',
            'unpaidTotal',
            'paidCount',
            'paidTotal',
            'tables',
            'activeOpenBillsCount',
            'pendingOpenBillRequests'
        ));
    }

    /**
     * Kasir mengonfirmasi pembayaran pesanan (dari Belum Dibayar -> Lunas).
     * Mencatat ID Kasir dan Waktu Konfirmasi sesuai aturan backend.
     */
    public function confirmPayment(Request $request, Sale $sale): RedirectResponse
    {
        $user = $request->user();

        // Otorisasi hak akses di backend
        if (! $user->isCashier() && ! $user->isAdmin()) {
            abort(403, 'Anda tidak memiliki wewenang untuk mengonfirmasi pembayaran.');
        }

        if ($sale->payment_status === 'paid') {
            return back()->with('info', "Pesanan {$sale->transaction_number} sudah berstatus LUNAS sebelumnya.");
        }

        $validated = $request->validate([
            'payment_method' => ['nullable', 'string', 'in:cash,transfer,qris'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $method = $validated['payment_method'] ?? 'cash';
        $amountPaid = (float) $sale->total_amount;
        $amountTendered = isset($validated['amount_tendered']) && $validated['amount_tendered'] > 0
            ? (float) $validated['amount_tendered']
            : $amountPaid;

        if ($method === 'cash' && $amountTendered < $amountPaid) {
            return back()->withErrors([
                'amount_tendered' => 'Uang yang diterima kurang dari total tagihan pesanan (Rp '.number_format($amountPaid, 0, ',', '.').').',
            ]);
        }

        $changeAmount = max(0, $amountTendered - $amountPaid);

        DB::transaction(function () use ($sale, $user, $method, $amountPaid, $amountTendered, $changeAmount, $validated) {
            // 1. Update status pembayaran pada pesanan
            $sale->update([
                'payment_status' => 'paid',
                'status' => 'completed',
                'user_id' => $sale->user_id ?: $user->id,
                'confirmed_by' => $user->id,
                'confirmed_at' => now(),
            ]);

            // 2. Simpan atau perbarui data pembayaran
            Payment::updateOrCreate(
                ['sale_id' => $sale->id],
                [
                    'method' => $method,
                    'amount_paid' => $amountPaid,
                    'amount_tendered' => $amountTendered,
                    'change_amount' => $changeAmount,
                    'status' => 'paid',
                    'reference_number' => $validated['reference_number'] ?? null,
                    'verified_by' => $user->id,
                    'paid_at' => now(),
                ]
            );

            // 3. Jika pesanan terhubung ke Open Bill, periksa apakah semua pesanan di open bill sudah lunas
            if ($sale->open_bill_id) {
                $openBill = OpenBill::find($sale->open_bill_id);
                if ($openBill && $openBill->isOpen()) {
                    $hasUnpaidRemaining = Sale::where('open_bill_id', $openBill->id)
                        ->where('payment_status', 'unpaid')
                        ->exists();

                    if (! $hasUnpaidRemaining) {
                        $openBill->update([
                            'status' => 'paid',
                            'closed_by' => $user->id,
                            'closed_at' => now(),
                        ]);
                    }
                }
            }
        });

        return back()->with('success', "Pembayaran untuk pesanan {$sale->transaction_number} (Meja {$sale->table_number}) berhasil dikonfirmasi LUNAS.");
    }
}
