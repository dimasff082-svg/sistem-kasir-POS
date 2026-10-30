<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\SimpleXlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

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
        $averageTransaction = $totalTransactions > 0 ? round($totalRevenue / $totalTransactions) : 0;

        // Items sold in period
        $itemsQuery = SaleItem::whereHas('sale', function ($q) use ($startDate, $endDate) {
            $q->where('status', 'completed')
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate);
        });

        $totalItemsSold = (clone $itemsQuery)->sum('quantity');

        // Breakdown per payment method
        $paymentBreakdown = Payment::select('method', DB::raw('COUNT(*) as total_count'), DB::raw('SUM(amount_paid) as total_amount'))
            ->whereHas('sale', function ($q) use ($startDate, $endDate) {
                $q->where('status', 'completed')
                    ->whereDate('transaction_date', '>=', $startDate)
                    ->whereDate('transaction_date', '<=', $endDate);
            })
            ->groupBy('method')
            ->get();

        // Top products sold in period
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
            ->take(10)
            ->get();

        // Detailed sales list
        $sales = (clone $salesQuery)->with(['user', 'payment', 'items'])
            ->latest('transaction_date')
            ->paginate(15)
            ->withQueryString();

        return view('owner.reports.index', compact(
            'sales',
            'totalRevenue',
            'totalTransactions',
            'totalItemsSold',
            'averageTransaction',
            'paymentBreakdown',
            'topProducts',
            'startDate',
            'endDate'
        ));
    }

    public function exportExcel(Request $request): Response
    {
        $startDate = $request->filled('start_date') ? $request->input('start_date') : now()->startOfMonth()->toDateString();
        $endDate = $request->filled('end_date') ? $request->input('end_date') : now()->toDateString();

        if ($startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        $fileName = 'laporan-penjualan-pemilik-'.$startDate.'-sd-'.$endDate.'.xlsx';

        $sales = Sale::with(['user', 'payment', 'items'])
            ->where('status', 'completed')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->latest('transaction_date')
            ->get();

        $headers = [
            'No. Transaksi',
            'Tanggal & Waktu',
            'Kasir Bertugas',
            'Metode Pembayaran',
            'Total Penjualan / Omzet (Rp)',
            'Total Item Terjual',
            'Status Transaksi',
        ];

        $rows = [];
        $grandTotalSales = 0;
        $grandTotalItems = 0;

        foreach ($sales as $sale) {
            $itemCount = (int) $sale->items->sum('quantity');

            $grandTotalSales += (float) $sale->total_amount;
            $grandTotalItems += $itemCount;

            $rows[] = [
                $sale->transaction_number,
                $sale->transaction_date->format('Y-m-d H:i:s'),
                $sale->user->name ?? '-',
                strtoupper($sale->payment->method ?? 'TUNAI'),
                (float) $sale->total_amount,
                $itemCount,
                ucfirst($sale->status),
            ];
        }

        $summaryRow = [
            'TOTAL KESELURUHAN',
            '',
            '',
            '',
            $grandTotalSales,
            $grandTotalItems,
            count($sales).' transaksi berhasil',
        ];

        return SimpleXlsxExporter::download(
            $fileName,
            'Laporan Keuangan Pemilik',
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
