@extends('layouts.app')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Laporan & Analisis Penjualan</h2>
            <p class="text-sm text-slate-500 mt-0.5">Pantau pendapatan omzet, estimasi laba kotor, dan performa produk toko.</p>
        </div>
        <a href="{{ route('admin.reports.export_excel', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
            class="inline-flex items-center gap-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs transition hover:scale-102 active:scale-98">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Ekspor Excel (.xlsx)
        </a>
    </div>

    @php
        $todayDate = now()->toDateString();
        $startOfMonthDate = now()->startOfMonth()->toDateString();
        $isTodayActive = ($startDate === $todayDate && $endDate === $todayDate);
        $isMonthActive = ($startDate === $startOfMonthDate && $endDate === $todayDate);
    @endphp

    <!-- Date Range Picker Form -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
        <form id="report-filter-form" action="{{ route('admin.reports.index') }}" method="GET" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-slate-500 whitespace-nowrap">Rentang Periode:</span>
                <input type="date" id="start_date" name="start_date" value="{{ $startDate }}"
                    class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <span class="text-xs text-slate-400 font-bold">s/d</span>
                <input type="date" id="end_date" name="end_date" value="{{ $endDate }}"
                    class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">

                <button type="submit" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs transition hover:scale-102 active:scale-98">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Terapkan
                </button>
            </div>

            <!-- Preset Filters with clear active state -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-400">Pilihan Cepat:</span>
                <button type="button" onclick="applyPresetPeriod('{{ $todayDate }}', '{{ $todayDate }}')"
                    class="inline-flex items-center gap-1.5 text-xs font-bold px-3.5 py-2 rounded-xl transition cursor-pointer {{ $isTodayActive ? 'bg-indigo-600 text-white shadow-xs ring-2 ring-indigo-400/50' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    @if($isTodayActive)
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @endif
                    Hari Ini
                </button>
                <button type="button" onclick="applyPresetPeriod('{{ $startOfMonthDate }}', '{{ $todayDate }}')"
                    class="inline-flex items-center gap-1.5 text-xs font-bold px-3.5 py-2 rounded-xl transition cursor-pointer {{ $isMonthActive ? 'bg-indigo-600 text-white shadow-xs ring-2 ring-indigo-400/50' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                    @if($isMonthActive)
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @endif
                    Bulan Ini
                </button>
            </div>
        </form>

        <div class="mt-3 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full {{ $isTodayActive ? 'bg-indigo-500' : ($isMonthActive ? 'bg-emerald-500' : 'bg-amber-500') }}"></span>
                <span>Periode Aktif: 
                    <strong class="text-slate-800">
                        @if($isTodayActive)
                            Hari Ini ({{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMMM Y') }})
                        @elseif($isMonthActive)
                            Bulan Ini ({{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMMM Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y') }})
                        @else
                            {{ \Carbon\Carbon::parse($startDate)->isoFormat('D MMMM Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->isoFormat('D MMMM Y') }}
                        @endif
                    </strong>
                </span>
            </div>
            <span class="hidden sm:inline text-slate-400 text-[11px]">Ditemukan {{ $totalTransactions }} transaksi selesai</span>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Total Omzet Penjualan</span>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </div>
            <p class="text-xs text-slate-500 mt-1">Bruto seluruh penjualan selesai</p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Estimasi Laba Kotor</span>
            <div class="text-2xl font-black text-emerald-600 tracking-tight">
                Rp {{ number_format($totalGrossProfit, 0, ',', '.') }}
            </div>
            <p class="text-xs text-slate-500 mt-1">Omzet dikurangi harga beli pokok</p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Jumlah Struk Selesai</span>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                {{ number_format($totalTransactions) }} <span class="text-sm font-normal text-slate-400">transaksi</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Telah dibayar & tidak dibatalkan</p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-2">Total Barang Terjual</span>
            <div class="text-2xl font-black text-slate-900 tracking-tight">
                {{ number_format($totalItemsSold) }} <span class="text-sm font-normal text-slate-400">kuantitas</span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Akumulasi kuantitas produk keluar</p>
        </div>
    </div>

    <!-- Section 2: Payment Method Breakdown & Top Products -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Breakdown Metode Pembayaran -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <h3 class="font-bold text-base text-slate-800 mb-4">Penjualan per Metode Pembayaran</h3>
            <div class="space-y-3">
                @forelse($paymentBreakdown as $pay)
                    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-sm text-slate-900 block uppercase">{{ $pay->method }}</span>
                            <span class="text-xs text-slate-500">{{ $pay->total_count }} kali pembayaran</span>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-base text-slate-900">
                                Rp {{ number_format($pay->total_amount, 0, ',', '.') }}
                            </span>
                            <span class="block text-[11px] text-slate-400">
                                {{ $totalRevenue > 0 ? round(($pay->total_amount / $totalRevenue) * 100, 1) : 0 }}% dari omzet
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 text-center py-6">Belum ada transaksi pada periode ini.</p>
                @endforelse
            </div>
        </div>

        <!-- Top 10 Best Sellers -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
            <h3 class="font-bold text-base text-slate-800 mb-4">10 Produk Terlaris (Periode Ini)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-100 text-slate-400 uppercase font-semibold">
                            <th class="pb-2">Barang</th>
                            <th class="pb-2 text-right">Terjual</th>
                            <th class="pb-2 text-right">Omzet</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($topProducts as $top)
                            <tr class="hover:bg-slate-50">
                                <td class="py-2.5">
                                    <div class="font-bold text-slate-900">{{ $top->product_name_snapshot }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $top->sku_snapshot }}</div>
                                </td>
                                <td class="py-2.5 text-right font-extrabold text-slate-800">{{ number_format($top->total_qty) }}</td>
                                <td class="py-2.5 text-right font-bold text-emerald-600">Rp {{ number_format($top->total_revenue, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-slate-400">Tidak ada produk terjual pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section 3: Daily Summary Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6">
        <h3 class="font-bold text-base text-slate-800 mb-4">Rincian Penjualan Harian</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <th class="py-3 px-6">Tanggal</th>
                        <th class="py-3 px-6 text-center">Jumlah Transaksi</th>
                        <th class="py-3 px-6 text-right">Total Omzet Harian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($dailySales as $day)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-6 font-bold text-slate-800">
                                {{ \Carbon\Carbon::parse($day->date)->translatedFormat('l, d F Y') }}
                            </td>
                            <td class="py-3 px-6 text-center font-semibold text-slate-600">
                                {{ $day->trx_count }} transaksi
                            </td>
                            <td class="py-3 px-6 text-right font-black text-emerald-600">
                                Rp {{ number_format($day->total_omzet, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-slate-400 text-sm">
                                Tidak ada catatan penjualan pada rentang tanggal yang dipilih.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function applyPresetPeriod(startDate, endDate) {
        var startInput = document.getElementById('start_date');
        var endInput = document.getElementById('end_date');
        var form = document.getElementById('report-filter-form');
        if (startInput && endInput && form) {
            startInput.value = startDate;
            endInput.value = endDate;
            form.submit();
        }
    }
</script>
@endsection
