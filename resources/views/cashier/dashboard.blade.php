@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto space-y-8">
    <!-- Welcome & Hero Action Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-700 text-white p-8 sm:p-10 shadow-xl shadow-emerald-700/10">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-white/20 text-white backdrop-blur-md mb-3">
                    <span class="w-2 h-2 rounded-full bg-emerald-300"></span>
                    Terminal Kasir Aktif
                </span>
                <h2 class="text-3xl font-extrabold tracking-tight">Selamat Bertugas, {{ auth()->user()->name }}!</h2>
                <p class="text-emerald-100 text-sm mt-1 max-w-lg">
                    Siap melayani pembeli dengan cepat dan akurat. Gunakan kolom pencarian untuk menemukan barang tanpa scanner.
                </p>
            </div>
            <div>
                <a href="{{ route('pos.index') }}" class="inline-flex items-center gap-3 bg-white text-emerald-800 hover:bg-emerald-50 text-sm font-extrabold px-6 py-4 rounded-2xl shadow-lg transition transform hover:scale-105 active:scale-95">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Mulai Transaksi Baru (POS)
                </a>
            </div>
        </div>
        <!-- Decorative Glow -->
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
    </div>

    <!-- Today's Cashier Statistics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Penjualan Anda Hari Ini</span>
            <div class="text-3xl font-black text-slate-900 tracking-tight">
                Rp {{ number_format($salesToday, 0, ',', '.') }}
            </div>
            <p class="text-xs text-slate-500 mt-2">Total nominal transaksi yang telah berhasil Anda selesaikan.</p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Jumlah Struk Selesai</span>
            <div class="text-3xl font-black text-slate-900 tracking-tight">
                {{ number_format($trxTodayCount) }} <span class="text-base font-normal text-slate-400">transaksi</span>
            </div>
            <p class="text-xs text-slate-500 mt-2">Jumlah pelanggan yang telah Anda layani hari ini.</p>
        </div>
    </div>

    <!-- Recent Sales Handled by this Cashier -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-bold text-base text-slate-800">Transaksi Terakhir Anda</h3>
                <p class="text-xs text-slate-500">Struk digital dari penjualan yang baru saja Anda layani.</p>
            </div>
            <a href="{{ route('cashier.transactions.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800">
                Lihat Seluruh Riwayat Anda &rarr;
            </a>
        </div>

        @if($recentSales->isEmpty())
            <div class="p-10 text-center text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <p class="text-sm font-medium">Anda belum melakukan transaksi hari ini.</p>
                <a href="{{ route('pos.index') }}" class="inline-block mt-3 text-xs font-bold text-emerald-600 hover:underline">
                    Klik di sini untuk membuka mesin kasir &rarr;
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                            <th class="pb-3">No. Transaksi</th>
                            <th class="pb-3">Waktu</th>
                            <th class="pb-3">Metode</th>
                            <th class="pb-3 text-right">Total</th>
                            <th class="pb-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentSales as $sale)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 font-mono text-xs font-bold text-emerald-700">
                                    {{ $sale->transaction_number }}
                                </td>
                                <td class="py-3 text-xs text-slate-500">
                                    {{ $sale->transaction_date->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3 text-xs">
                                    <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-700">
                                        {{ $sale->payment->methodLabel() ?? 'Tunai' }}
                                    </span>
                                </td>
                                <td class="py-3 text-right font-bold text-slate-900 text-xs">
                                    Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3 text-right">
                                    <a href="{{ route('pos.receipt', $sale->id) }}" class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-semibold transition">
                                        Lihat Struk
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
