@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Riwayat Transaksi Saya</h2>
            <p class="text-sm text-slate-500 mt-0.5">Daftar struk penjualan yang telah Anda layani.</p>
        </div>
        <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-sm transition">
            Transaksi Baru (POS)
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
        <form action="{{ route('cashier.transactions.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-6">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="No. Transaksi (TRX-...)"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs text-slate-800 font-mono">
            </div>

            <div class="sm:col-span-4 flex gap-1.5">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-1/2 bg-slate-50 border border-slate-200 rounded-xl px-2 py-2 text-[11px]">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-1/2 bg-slate-50 border border-slate-200 rounded-xl px-2 py-2 text-[11px]">
            </div>

            <div class="sm:col-span-2 flex gap-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold px-4 py-2 rounded-xl transition">
                    Saring
                </button>
                @if(request()->hasAny(['search', 'date_from', 'date_to']))
                    <a href="{{ route('cashier.transactions.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold px-3 py-2 rounded-xl transition flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-6">No. Transaksi</th>
                        <th class="py-3.5 px-6">Waktu Transaksi</th>
                        <th class="py-3.5 px-6 text-center">Metode Bayar</th>
                        <th class="py-3.5 px-6 text-center">Jumlah Item</th>
                        <th class="py-3.5 px-6 text-right">Total Transaksi</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-4 px-6 font-mono text-xs font-bold text-emerald-700">
                                {{ $sale->transaction_number }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500">
                                {{ $sale->transaction_date->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700">
                                    {{ $sale->payment?->methodLabel() ?? 'Tunai' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center text-xs font-bold text-slate-600">
                                {{ $sale->items->sum('quantity') }} item
                            </td>
                            <td class="py-4 px-6 text-right text-xs font-black text-slate-900">
                                Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a href="{{ route('cashier.transactions.show', $sale->id) }}"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold transition">
                                    Buka Struk
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-slate-400 text-sm">
                                Belum ada riwayat transaksi yang Anda layani.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sales->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
