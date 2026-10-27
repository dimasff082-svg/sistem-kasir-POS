@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Detail Transaksi Penjualan</h2>
            <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $sale->transaction_number }} &bull; {{ $sale->transaction_date->format('d F Y, H:i:s') }} WIB</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Buka / Cetak Struk Digital
            </a>
            <a href="{{ route('admin.transactions.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3.5 py-2.5 rounded-xl transition">
                &larr; Kembali
            </a>
        </div>
    </div>

    <!-- Metadata Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Kasir Bertugas</span>
            <span class="text-base font-bold text-slate-900">{{ $sale->user->name ?? '-' }}</span>
            <span class="text-xs text-slate-400 block mt-0.5">{{ $sale->user->email ?? '' }}</span>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Metode & Status</span>
            <div class="flex items-center gap-2 mt-1">
                <span class="text-sm font-bold text-slate-900">{{ $sale->payment->methodLabel() ?? 'Tunai' }}</span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800">Lunas</span>
            </div>
            <span class="text-xs text-slate-500 block mt-1">Bayar: Rp {{ number_format($sale->payment->amount_tendered, 0, ',', '.') }} | Kembali: Rp {{ number_format($sale->payment->change_amount, 0, ',', '.') }}</span>
        </div>
        <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Tagihan</span>
            <span class="text-2xl font-black text-emerald-600">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</span>
            <span class="text-xs text-slate-400 block mt-0.5">{{ $sale->items->sum('quantity') }} total barang terjual</span>
        </div>
    </div>

    <!-- Items Detail Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100">
            <h3 class="font-bold text-base text-slate-800">Rincian Barang Terjual (Data Snapshot)</h3>
            <p class="text-xs text-slate-400">Harga dan nama saat transaksi berlangsung tetap terlindungi meskipun data barang di master berubah.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-6">SKU Snapshot</th>
                        <th class="py-3.5 px-6">Nama Barang Snapshot</th>
                        <th class="py-3.5 px-6 text-center">Jumlah</th>
                        <th class="py-3.5 px-6 text-right">Harga Satuan Saat Itu</th>
                        <th class="py-3.5 px-6 text-right">Subtotal Baris</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @foreach($sale->items as $it)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-4 px-6 font-mono text-xs font-bold text-slate-600">
                                {{ $it->sku_snapshot }}
                            </td>
                            <td class="py-4 px-6 font-bold text-slate-900">
                                {{ $it->product_name_snapshot }}
                            </td>
                            <td class="py-4 px-6 text-center font-bold text-slate-800">
                                {{ $it->quantity }}
                            </td>
                            <td class="py-4 px-6 text-right text-xs text-slate-600">
                                Rp {{ number_format($it->unit_price, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-6 text-right font-black text-slate-900 text-sm">
                                Rp {{ number_format($it->line_total, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="bg-slate-50 font-black text-slate-900">
                        <td colspan="4" class="py-4 px-6 text-right text-xs uppercase tracking-wider">Total Transaksi:</td>
                        <td class="py-4 px-6 text-right text-base text-emerald-700">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
