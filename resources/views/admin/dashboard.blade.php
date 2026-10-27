@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <!-- Header Page Info -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2">
        <div>
            <h2 class="text-2xl font-serif-classic font-bold text-[#262017] tracking-tight">Ringkasan Aktivitas Toko</h2>
            <p class="text-sm text-[#736858] mt-1">Pantau penjualan harian, perputaran stok, dan performa kasir secara terpusat.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-2 bg-[#ffffff] hover:bg-[#f5f1e8] border border-[#dcd4c5] text-[#362f25] text-xs font-semibold px-4 py-2.5 rounded-xl shadow-xs transition">
                <svg class="w-4 h-4 text-[#8a681c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Lihat Laporan Lengkap
            </a>
        </div>
    </div>

    <!-- 4 Summary KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Omzet Hari Ini -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Penjualan Hari Ini</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                Rp {{ number_format($salesToday, 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                <span class="inline-block w-2 h-2 rounded-full bg-emerald-500"></span>
                Total transaksi berhasil hari ini
            </div>
        </div>

        <!-- Total Transaksi Selesai -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Transaksi Selesai</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                {{ number_format($trxTodayCount) }} <span class="text-sm font-normal text-slate-400">struk</span>
            </div>
            <div class="text-xs text-slate-500 mt-2">
                Dilayani oleh tim kasir hari ini
            </div>
        </div>

        <!-- Barang Aktif -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Barang Aktif</span>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                {{ number_format($activeProductsCount) }} <span class="text-sm font-normal text-slate-400">produk</span>
            </div>
            <div class="text-xs text-slate-500 mt-2">
                Siap dijual di sistem kasir
            </div>
        </div>

        <!-- Stok Menipis -->
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs hover:shadow-md transition">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Stok Menipis</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-black {{ $lowStockCount > 0 ? 'text-rose-600' : 'text-slate-900' }} tracking-tight">
                {{ number_format($lowStockCount) }} <span class="text-sm font-normal text-slate-400">item</span>
            </div>
            <div class="text-xs text-slate-500 mt-2">
                Di bawah ambang batas minimum
            </div>
        </div>
    </div>

    <!-- Section 2: Low Stock Alert & Top Selling Products -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Produk Stok Menipis -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                    <h3 class="font-bold text-base text-slate-800">Perhatian: Stok Menipis</h3>
                </div>
                <a href="{{ route('admin.stock.index', ['filter' => 'low_stock']) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                    Kelola Stok &rarr;
                </a>
            </div>

            @if($lowStockProducts->isEmpty())
                <div class="p-8 text-center text-slate-400 my-auto">
                    <svg class="w-10 h-10 mx-auto mb-2 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium text-slate-600">Semua stok barang dalam kondisi aman!</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider font-semibold">
                                <th class="pb-2">Barang</th>
                                <th class="pb-2">SKU</th>
                                <th class="pb-2 text-right">Sisa Stok</th>
                                <th class="pb-2 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @foreach($lowStockProducts as $p)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3 text-slate-800 font-semibold">{{ $p->name }}</td>
                                    <td class="py-3 text-slate-500 font-mono text-[11px]">{{ $p->sku }}</td>
                                    <td class="py-3 text-right">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $p->stock <= 0 ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $p->stock }} {{ $p->unit }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <a href="{{ route('admin.stock.adjust', $p->id) }}" class="text-xs font-semibold text-indigo-600 hover:underline">
                                            Sesuaikan
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Produk Terlaris -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <h3 class="font-bold text-base text-slate-800">Produk Paling Laris</h3>
                </div>
                <span class="text-xs text-slate-400">Berdasarkan kuantitas terjual</span>
            </div>

            @if($topProducts->isEmpty())
                <div class="p-8 text-center text-slate-400 my-auto">
                    <p class="text-sm">Belum ada data penjualan tercatat.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 uppercase tracking-wider font-semibold">
                                <th class="pb-2">Nama Produk</th>
                                <th class="pb-2 text-right">Terjual</th>
                                <th class="pb-2 text-right">Total Pendapatan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @foreach($topProducts as $top)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3 text-slate-800">
                                        <div class="font-semibold">{{ $top->product_name_snapshot }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $top->sku_snapshot }}</div>
                                    </td>
                                    <td class="py-3 text-right font-bold text-slate-900">
                                        {{ number_format($top->total_sold) }} item
                                    </td>
                                    <td class="py-3 text-right text-emerald-600 font-bold">
                                        Rp {{ number_format($top->total_revenue, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <!-- Section 3: Recent Transactions Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="font-bold text-base text-slate-800">Transaksi Terbaru</h3>
                <p class="text-xs text-slate-500">Daftar transaksi penjualan paling mutakhir yang selesai diproses.</p>
            </div>
            <a href="{{ route('admin.transactions.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                Lihat Seluruh Riwayat &rarr;
            </a>
        </div>

        @if($recentSales->isEmpty())
            <div class="p-10 text-center text-slate-400">
                <p class="text-sm">Belum ada transaksi yang dilakukan.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 text-xs uppercase tracking-wider font-semibold">
                            <th class="pb-3">No. Transaksi</th>
                            <th class="pb-3">Waktu</th>
                            <th class="pb-3">Kasir</th>
                            <th class="pb-3">Metode</th>
                            <th class="pb-3 text-right">Total Belanja</th>
                            <th class="pb-3 text-center">Status</th>
                            <th class="pb-3 text-right">Struk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($recentSales as $sale)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 font-mono text-xs font-bold text-indigo-600">
                                    {{ $sale->transaction_number }}
                                </td>
                                <td class="py-3.5 text-xs text-slate-500">
                                    {{ $sale->transaction_date->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-3.5 text-xs text-slate-700 font-medium">
                                    {{ $sale->user->name ?? 'Kasir' }}
                                </td>
                                <td class="py-3.5 text-xs">
                                    <span class="inline-flex px-2 py-0.5 rounded-md font-medium text-[11px] bg-slate-100 text-slate-700">
                                        {{ $sale->payment->methodLabel() ?? 'Tunai' }}
                                    </span>
                                </td>
                                <td class="py-3.5 text-right font-bold text-slate-900 text-xs">
                                    Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                                </td>
                                <td class="py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Selesai
                                    </span>
                                </td>
                                <td class="py-3.5 text-right">
                                    <a href="{{ route('pos.receipt', $sale->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Struk
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
