<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SimpleXlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->startOfMonth()->toDateString();
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->toDateString();

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $salesQuery = Sale::where('status', 'completed')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate);

        $totalRevenue = (clone $salesQuery)->sum('total_amount');
        $totalTransactions = (clone $salesQuery)->count();

        // Items sold and estimated gross profit
        $itemsQuery = SaleItem::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->where('status', 'completed')
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate);
        });

        $totalItemsSold = (clone $itemsQuery)->sum('quantity');
        $totalGrossProfit = (clone $itemsQuery)->selectRaw('SUM(line_total - (unit_purchase_price * quantity)) as profit')->value('profit') ?? 0;

        // Breakdown per payment method
        $paymentBreakdown = Payment::select('method', DB::raw('COUNT(*) as total_count'), DB::raw('SUM(amount_paid) as total_amount'))
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'completed')
                    ->whereDate('transaction_date', '>=', $startDate)
                    ->whereDate('transaction_date', '<=', $endDate);
            })
            ->groupBy('method')
            ->get();

        // Top 10 products sold in period
        $topProducts = SaleItem::select(
            'product_name_snapshot',
            'sku_snapshot',
            DB::raw('SUM(quantity) as total_qty'),
            DB::raw('SUM(line_total) as total_revenue')
        )
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'completed')
                    ->whereDate('transaction_date', '>=', $startDate)
                    ->whereDate('transaction_date', '<=', $endDate);
            })
            ->groupBy('product_name_snapshot', 'sku_snapshot')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        // Daily breakdown in period
        $dailySales = Sale::select(
            DB::raw('DATE(transaction_date) as date'),
            DB::raw('COUNT(*) as trx_count'),
            DB::raw('SUM(total_amount) as total_omzet')
        )
            ->where('status', 'completed')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->groupBy(DB::raw('DATE(transaction_date)'))
            ->orderBy('date')
            ->get();

        return view('admin.reports.index', compact(
            'startDate',
            'endDate',
            'totalRevenue',
            'totalTransactions',
            'totalItemsSold',
            'totalGrossProfit',
            'paymentBreakdown',
            'topProducts',
            'dailySales'
        ));
    }

    public function exportExcel(Request $request): BinaryFileResponse
    {
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->startOfMonth()->toDateString();
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->toDateString();

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $sales = Sale::with(['user', 'payment', 'items'])
            ->where('status', 'completed')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->orderBy('transaction_date')
            ->get();

        $fileName = "laporan-penjualan-admin-{$startDate}-sd-{$endDate}.xlsx";

        $headers = [
            'No. Transaksi',
            'Tanggal & Waktu',
            'Kasir Bertugas',
            'Metode Pembayaran',
            'Subtotal (Rp)',
            'Total Akhir (Rp)',
            'Jumlah Item',
            'Rincian Barang Terjual',
        ];

        $rows = [];
        $totalSubtotal = 0;
        $totalRevenue = 0;
        $totalItemsCount = 0;

        foreach ($sales as $sale) {
            $itemDetails = $sale->items->map(function ($it) {
                return "{$it->product_name_snapshot} ({$it->quantity}x @ Rp ".number_format($it->unit_price, 0, ',', '.').')';
            })->implode('; ');

            $itemsQty = (int) $sale->items->sum('quantity');
            $totalSubtotal += (float) $sale->subtotal;
            $totalRevenue += (float) $sale->total_amount;
            $totalItemsCount += $itemsQty;

            $rows[] = [
                $sale->transaction_number,
                $sale->transaction_date->format('Y-m-d H:i:s'),
                $sale->user->name ?? '-',
                $sale->payment?->methodLabel() ?? 'Tunai',
                (float) $sale->subtotal,
                (float) $sale->total_amount,
                $itemsQty,
                $itemDetails,
            ];
        }

        $summaryRow = [
            'TOTAL KESELURUHAN',
            '',
            '',
            '',
            $totalSubtotal,
            $totalRevenue,
            $totalItemsCount,
            count($sales).' transaksi berhasil',
        ];

        return SimpleXlsxExporter::download(
            $fileName,
            'Laporan Penjualan',
            $headers,
            $rows,
            $summaryRow
        );
    }

    public function exportCsv(Request $request): BinaryFileResponse
    {
        return $this->exportExcel($request);
    }
}
