@extends('layouts.app')

@push('styles')
<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printable-receipt, #printable-receipt * {
        visibility: visible;
    }
    #printable-receipt {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: 80mm;
        margin: 0 auto;
        padding: 10px;
        box-shadow: none !important;
        border: none !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>
@endpush

@section('content')
<div class="max-w-xl mx-auto space-y-6">
    <!-- Top Actions / Nav -->
    <div class="flex items-center justify-between no-print">
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
            </span>
            <div>
                <h2 class="text-xl font-black text-slate-800">Transaksi Berhasil!</h2>
                <p class="text-xs text-slate-500">Struk digital telah dibuat dan tersimpan aman di sistem.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs transition">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Cetak Struk
            </button>
            <a href="{{ route('pos.index') }}"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-md shadow-emerald-600/20 transition transform active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Transaksi Baru
            </a>
        </div>
    </div>

    <!-- Digital Receipt Card -->
    <div id="printable-receipt" class="bg-white rounded-3xl border border-slate-200/90 shadow-xl p-8 font-sans max-w-md mx-auto">
        <!-- Store Header -->
        <div class="text-center pb-6 border-b border-dashed border-slate-300">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-900 text-white flex items-center justify-center font-black text-xl mb-3 shadow-md">
                POS
            </div>
            <h3 class="font-extrabold text-lg text-slate-900 tracking-tight">TOKO KASIR MODERN</h3>
            <p class="text-xs text-slate-500 mt-0.5">Jl. Merdeka No. 45, Indonesia</p>
            <p class="text-xs text-slate-400">Telp: 0812-3456-7890 &bull; Struk Resmi</p>
        </div>

        <!-- Meta Info -->
        <div class="py-4 border-b border-dashed border-slate-300 text-xs space-y-1.5 font-mono">
            <div class="flex justify-between">
                <span class="text-slate-400">No. Transaksi</span>
                <span class="font-bold text-slate-900">{{ $sale->transaction_number }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Waktu</span>
                <span class="text-slate-700">{{ $sale->transaction_date->format('d/m/Y H:i:s') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Kasir</span>
                <span class="text-slate-700 font-semibold">{{ $sale->user->name ?? 'Kasir' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400">Status</span>
                <span class="text-emerald-600 font-bold uppercase tracking-wider">{{ $sale->status }}</span>
            </div>
        </div>

        <!-- Items Table -->
        <div class="py-4 border-b border-dashed border-slate-300">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-slate-400 text-left font-mono border-b border-slate-100 pb-2">
                        <th class="pb-1.5">Item</th>
                        <th class="pb-1.5 text-center">Qty</th>
                        <th class="pb-1.5 text-right">Harga</th>
                        <th class="pb-1.5 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-mono">
                    @foreach($sale->items as $item)
                        <tr>
                            <td class="py-2 pr-1">
                                <div class="font-bold text-slate-900 leading-tight">{{ $item->product_name_snapshot }}</div>
                                <div class="text-[10px] text-slate-400">{{ $item->sku_snapshot }}</div>
                            </td>
                            <td class="py-2 text-center text-slate-700">{{ $item->quantity }}</td>
                            <td class="py-2 text-right text-slate-600">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-2 text-right font-bold text-slate-900">{{ number_format($item->line_total, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Summary Totals -->
        <div class="py-4 border-b border-dashed border-slate-300 text-xs font-mono space-y-2">
            <div class="flex justify-between text-slate-600">
                <span>Subtotal Item</span>
                <span>Rp {{ number_format($sale->subtotal, 0, ',', '.') }}</span>
            </div>

            <div class="flex justify-between text-base font-black text-slate-900 pt-2 border-t border-slate-100">
                <span>TOTAL TAGIHAN</span>
                <span class="text-emerald-700">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</span>
            </div>

            <div class="pt-2 border-t border-slate-100 space-y-1">
                <div class="flex justify-between text-slate-600">
                    <span>Metode Bayar</span>
                    <span class="font-bold uppercase">{{ $sale->payment->methodLabel() ?? 'TUNAI' }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Uang Diterima</span>
                    <span>Rp {{ number_format($sale->payment->amount_tendered ?? $sale->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between font-bold text-slate-900">
                    <span>Uang Kembalian</span>
                    <span>Rp {{ number_format($sale->payment->change_amount ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Footer Thank You Note -->
        <div class="text-center pt-6 space-y-1 text-xs text-slate-400">
            <p class="font-bold text-slate-700">Terima kasih atas kunjungan Anda!</p>
            <p class="text-[11px]">Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa bukti struk ini.</p>
        </div>
    </div>
</div>
@endsection
