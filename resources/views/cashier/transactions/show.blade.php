@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Struk Transaksi</h2>
            <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $sale->transaction_number }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank"
                class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs transition">
                Cetak Struk
            </a>
            <a href="{{ route('cashier.transactions.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3.5 py-2.5 rounded-xl transition">
                &larr; Kembali
            </a>
        </div>
    </div>

    <!-- Embedded receipt view -->
    <div class="bg-white rounded-3xl border border-slate-200/90 shadow-xl p-8 max-w-md mx-auto">
        <div class="text-center pb-6 border-b border-dashed border-slate-300">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-slate-900 text-white flex items-center justify-center font-black text-xl mb-3 shadow-md">
                POS
            </div>
            <h3 class="font-extrabold text-lg text-slate-900 tracking-tight">TOKO KASIR MODERN</h3>
            <p class="text-xs text-slate-500 mt-0.5">Jl. Merdeka No. 45, Indonesia</p>
            <p class="text-xs text-slate-400">Telp: 0812-3456-7890</p>
        </div>

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
        </div>

        <div class="py-4 border-b border-dashed border-slate-300">
            <table class="w-full text-xs font-mono">
                <thead>
                    <tr class="text-slate-400 text-left border-b border-slate-100 pb-2">
                        <th class="pb-1.5">Item</th>
                        <th class="pb-1.5 text-center">Qty</th>
                        <th class="pb-1.5 text-right">Harga</th>
                        <th class="pb-1.5 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($sale->items as $item)
                        <tr>
                            <td class="py-2 pr-1">
                                <div class="font-bold text-slate-900">{{ $item->product_name_snapshot }}</div>
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

        <div class="py-4 text-xs font-mono space-y-1.5">
            <div class="flex justify-between text-base font-black text-slate-900 pt-1">
                <span>TOTAL</span>
                <span class="text-emerald-700">Rp {{ number_format($sale->total_amount, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-slate-600 pt-2 border-t border-slate-100">
                <span>Metode Bayar</span>
                <span class="font-bold uppercase">{{ $sale->payment->methodLabel() ?? 'TUNAI' }}</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Uang Diterima</span>
                <span>Rp {{ number_format($sale->payment->amount_tendered ?? $sale->total_amount, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between font-bold text-slate-900">
                <span>Kembalian</span>
                <span>Rp {{ number_format($sale->payment->change_amount ?? 0, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
