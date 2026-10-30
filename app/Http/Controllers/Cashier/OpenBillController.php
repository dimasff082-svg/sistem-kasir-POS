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
use Illuminate\Support\Str;
use Illuminate\View\View;

class OpenBillController extends Controller
{
    /**
     * Tampilan pengelolaan Open Bill untuk meja restoran.
     * Kasir hanya mengelola tagihan gabungan meja, TIDAK menambah/mengubah pesanan.
     */
    public function index(): View
    {
        $tables = RestaurantTable::with(['activeOpenBill.sales', 'requestedOpenBill'])
            ->orderBy('table_number')
            ->get();

        $pendingRequests = OpenBill::where('status', 'requested')
            ->latest('created_at')
            ->get();

        $activeOpenBills = OpenBill::with(['sales.items', 'opener'])
            ->where('status', 'open')
            ->latest('opened_at')
            ->get();

        $todayClosedBills = OpenBill::with(['closer'])
            ->where('status', 'paid')
            ->whereDate('closed_at', today())
            ->latest('closed_at')
            ->limit(10)
            ->get();

        return view('cashier.open_bills.index', compact('tables', 'pendingRequests', 'activeOpenBills', 'todayClosedBills'));
    }

    /**
     * Membuka sesi Open Bill untuk meja tertentu secara manual oleh kasir.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isCashier() && ! $user->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'table_number' => ['required', 'string', 'exists:restaurant_tables,table_number'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $tableNumber = $validated['table_number'];

        // Cek apakah meja sudah memiliki Open Bill aktif
        $existing = OpenBill::where('table_number', $tableNumber)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return back()->withErrors([
                'table_number' => "Meja {$tableNumber} sudah memiliki Open Bill aktif ({$existing->bill_number}).",
            ]);
        }

        DB::transaction(function () use ($tableNumber, $validated, $user) {
            $billNumber = 'BILL-'.now()->format('Ymd').'-'.strtoupper(Str::random(4));

            $bill = OpenBill::create([
                'bill_number' => $billNumber,
                'table_number' => $tableNumber,
                'status' => 'open',
                'opened_by' => $user->id,
                'opened_at' => now(),
                'total_amount' => 0,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Gabungkan pesanan pelanggan yang belum dibayar di meja ini ke dalam Open Bill ini
            Sale::where('table_number', $tableNumber)
                ->whereNull('open_bill_id')
                ->where('payment_status', 'unpaid')
                ->update(['open_bill_id' => $bill->id]);

            $bill->recalculateTotal();

            RestaurantTable::where('table_number', $tableNumber)->update(['status' => 'occupied']);
        });

        return back()->with('success', "Open Bill untuk Meja {$tableNumber} berhasil dibuka. Semua pesanan pelanggan di meja ini akan otomatis tergabung ke satu tagihan.");
    }

    /**
     * Kasir menyetujui (Approve) permintaan Open Bill yang dikirim oleh pelanggan dari meja.
     */
    public function approve(Request $request, OpenBill $openBill): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isCashier() && ! $user->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        if ($openBill->status !== 'requested') {
            return back()->with('info', "Permintaan Open Bill {$openBill->bill_number} sudah diproses sebelumnya.");
        }

        DB::transaction(function () use ($openBill, $user) {
            $openBill->update([
                'status' => 'open',
                'opened_by' => $user->id,
                'opened_at' => now(),
            ]);

            // Gabungkan pesanan yang belum dibayar di meja ini ke dalam Open Bill ini
            Sale::where('table_number', $openBill->table_number)
                ->whereNull('open_bill_id')
                ->where('payment_status', 'unpaid')
                ->update([
                    'open_bill_id' => $openBill->id,
                    'user_id' => DB::raw("COALESCE(user_id, {$user->id})"),
                ]);

            $openBill->recalculateTotal();

            RestaurantTable::where('table_number', $openBill->table_number)->update(['status' => 'occupied']);
        });

        return back()->with('success', "Permintaan Open Bill untuk Meja {$openBill->table_number} ({$openBill->bill_number}) disetujui. Meja kini berstatus Open Bill aktif.");
    }

    /**
     * Kasir menolak (Reject) permintaan Open Bill dari pelanggan.
     */
    public function reject(Request $request, OpenBill $openBill): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isCashier() && ! $user->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        if ($openBill->status !== 'requested') {
            return back()->with('info', "Permintaan Open Bill {$openBill->bill_number} sudah diproses sebelumnya.");
        }

        $openBill->update([
            'status' => 'rejected',
            'closed_by' => $user->id,
            'closed_at' => now(),
        ]);

        return back()->with('info', "Permintaan Open Bill untuk Meja {$openBill->table_number} telah ditolak.");
    }

    /**
     * Melihat detail tagihan Open Bill meja.
     */
    public function show(OpenBill $openBill): View
    {
        $openBill->load(['sales.items.product', 'sales.payment', 'opener', 'closer']);

        return view('cashier.open_bills.show', compact('openBill'));
    }

    /**
     * Mengonfirmasi pembayaran seluruh tagihan Open Bill dan menutup sesi meja.
     */
    public function confirmPayment(Request $request, OpenBill $openBill): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isCashier() && ! $user->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        if ($openBill->status === 'paid') {
            return back()->with('info', "Tagihan Open Bill {$openBill->bill_number} sudah lunas.");
        }

        $validated = $request->validate([
            'payment_method' => ['nullable', 'string', 'in:cash,transfer,qris'],
            'amount_tendered' => ['nullable', 'numeric', 'min:0'],
        ]);

        $method = $validated['payment_method'] ?? 'cash';
        $openBill->recalculateTotal();
        $totalAmount = (float) $openBill->total_amount;
        $tendered = isset($validated['amount_tendered']) && $validated['amount_tendered'] > 0
            ? (float) $validated['amount_tendered']
            : $totalAmount;

        if ($method === 'cash' && $tendered < $totalAmount) {
            return back()->withErrors([
                'amount_tendered' => 'Uang yang diterima kurang dari total tagihan Open Bill (Rp '.number_format($totalAmount, 0, ',', '.').').',
            ]);
        }

        DB::transaction(function () use ($openBill, $user, $method) {
            // Update seluruh pesanan yang tergabung di open bill ini menjadi LUNAS
            $sales = $openBill->sales;
            foreach ($sales as $sale) {
                if ($sale->payment_status !== 'paid') {
                    $sale->update([
                        'payment_status' => 'paid',
                        'status' => 'completed',
                        'user_id' => $sale->user_id ?: $user->id,
                        'confirmed_by' => $user->id,
                        'confirmed_at' => now(),
                    ]);

                    Payment::updateOrCreate(
                        ['sale_id' => $sale->id],
                        [
                            'method' => $method,
                            'amount_paid' => $sale->total_amount,
                            'amount_tendered' => $sale->total_amount,
                            'change_amount' => 0,
                            'status' => 'paid',
                            'verified_by' => $user->id,
                            'paid_at' => now(),
                        ]
                    );
                }
            }

            // Tutup Open Bill
            $openBill->update([
                'status' => 'paid',
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]);

            // Cek apakah meja sudah tidak memiliki pesanan belum bayar
            $hasUnpaid = Sale::where('table_number', $openBill->table_number)
                ->where('payment_status', 'unpaid')
                ->exists();

            if (! $hasUnpaid) {
                RestaurantTable::where('table_number', $openBill->table_number)->update(['status' => 'available']);
            }
        });

        return redirect()->route('cashier.open_bills.index')
            ->with('success', "Seluruh tagihan Open Bill Meja {$openBill->table_number} ({$openBill->bill_number}) senilai Rp ".number_format($totalAmount, 0, ',', '.').' berhasil dilunasi.');
    }
}
