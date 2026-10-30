@extends('layouts.app', ['title' => 'Laporan Penjualan - Pemilik Toko'])

@section('content')
<div class="space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-[#e5dfd3] pb-3">
        <div>
            <h2 class="text-2xl font-serif-classic font-bold text-[#262017] tracking-tight">Laporan Penjualan & Transaksi</h2>
            <p class="text-xs text-[#736858] mt-0.5">Analisis pendapatan omzet, volume menu terjual, dan rata-rata nilai transaksi cafe & resto.</p>
        </div>
        <a href="{{ route('owner.reports.export_excel', ['start_date' => $startDate, 'end_date' => $endDate]) }}"
            class="inline-flex items-center gap-2 bg-[#1b4332] hover:bg-[#2d6a4f] text-emerald-100 border border-emerald-500/30 text-xs font-bold px-4 py-2.5 rounded-xl shadow-xs transition hover:scale-102 active:scale-98">
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
    <div class="bg-white rounded-2xl border border-[#e5dfd3] shadow-xs p-4">
        <form id="owner-report-filter-form" action="{{ route('owner.reports.index') }}" method="GET" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold text-[#736858] whitespace-nowrap">Rentang Periode:</span>
                <input type="date" id="owner_start_date" name="start_date" value="{{ $startDate }}"
                    class="bg-[#faf7f0] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:outline-none focus:ring-2 focus:ring-[#c59b27]">
                <span class="text-xs text-[#8c8273] font-bold">s/d</span>
                <input type="date" id="owner_end_date" name="end_date" value="{{ $endDate }}"
                    class="bg-[#faf7f0] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:outline-none focus:ring-2 focus:ring-[#c59b27]">

                <button type="submit" class="inline-flex items-center gap-1.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-4 py-2 rounded-xl shadow-xs transition hover:scale-102 active:scale-98">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Terapkan
                </button>
            </div>

            <!-- Preset Filters with clear active state -->
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-[#8a7f70]">Pilihan Cepat:</span>
                <button type="button" onclick="applyOwnerPreset('{{ $todayDate }}', '{{ $todayDate }}')"
                    class="inline-flex items-center gap-1.5 text-xs font-bold px-3.5 py-2 rounded-xl transition cursor-pointer {{ $isTodayActive ? 'bg-[#c59b27] text-stone-950 shadow-xs ring-2 ring-[#a98119]/50' : 'bg-[#f2ece0] hover:bg-[#e8e0d0] text-[#362f25]' }}">
                    @if($isTodayActive)
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @endif
                    Hari Ini
                </button>
                <button type="button" onclick="applyOwnerPreset('{{ $startOfMonthDate }}', '{{ $todayDate }}')"
                    class="inline-flex items-center gap-1.5 text-xs font-bold px-3.5 py-2 rounded-xl transition cursor-pointer {{ $isMonthActive ? 'bg-[#c59b27] text-stone-950 shadow-xs ring-2 ring-[#a98119]/50' : 'bg-[#f2ece0] hover:bg-[#e8e0d0] text-[#362f25]' }}">
                    @if($isMonthActive)
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    @endif
                    Bulan Ini
                </button>
            </div>
        </form>

        <div class="mt-3 pt-3 border-t border-[#f2ede2] flex items-center justify-between text-xs text-[#786e60]">
            <div class="flex items-center gap-2">
                <span class="inline-block w-2 h-2 rounded-full {{ $isTodayActive ? 'bg-[#c59b27]' : ($isMonthActive ? 'bg-emerald-600' : 'bg-amber-600') }}"></span>
                <span>Periode Aktif: 
                    <strong class="text-[#262017]">
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
            <span class="hidden sm:inline text-[#8a7f70] text-[11px]">Ditemukan {{ $totalTransactions }} transaksi selesai</span>
        </div>
    </div>

    <!-- 4 KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-2xl p-6 border border-[#e5dfd3] shadow-xs">
            <span class="text-xs font-bold text-[#8a7f70] uppercase tracking-wider block mb-2">Total Omzet Penjualan</span>
            <div class="text-2xl font-serif-classic font-bold text-[#262017] tracking-tight">
                Rp {{ number_format($totalRevenue, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-[#786e60] mt-1">{{ number_format($totalTransactions) }} struk berhasil</p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-[#e5dfd3] shadow-xs">
            <span class="text-xs font-bold text-[#8a7f70] uppercase tracking-wider block mb-2">Total Transaksi Selesai</span>
            <div class="text-2xl font-serif-classic font-bold text-[#1b4332] tracking-tight">
                {{ number_format($totalTransactions) }} <span class="text-sm font-normal text-[#8a7f70]">struk</span>
            </div>
            <p class="text-[11px] text-[#786e60] mt-1">Pesanan lunas & selesai</p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-[#e5dfd3] shadow-xs">
            <span class="text-xs font-bold text-[#8a7f70] uppercase tracking-wider block mb-2">Total Menu Terjual</span>
            <div class="text-2xl font-serif-classic font-bold text-[#362f25] tracking-tight">
                {{ number_format($totalItemsSold) }} <span class="text-sm font-normal text-[#8a7f70]">porsi/item</span>
            </div>
            <p class="text-[11px] text-[#786e60] mt-1">Volume makanan & minuman</p>
        </div>

        <div class="bg-white rounded-2xl p-6 border border-[#e5dfd3] shadow-xs">
            <span class="text-xs font-bold text-[#8a7f70] uppercase tracking-wider block mb-2">Rata-rata Transaksi</span>
            <div class="text-2xl font-serif-classic font-bold text-[#1d3557] tracking-tight">
                Rp {{ number_format($averageTransaction, 0, ',', '.') }}
            </div>
            <p class="text-[11px] text-[#786e60] mt-1">Rata-rata belanja / pesanan</p>
        </div>
    </div>

    <!-- Payment Breakdown & Top Menu -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl border border-[#e5dfd3] shadow-xs p-6 space-y-4">
            <h3 class="font-serif-classic font-bold text-base text-[#262017] border-b border-[#f0ebd9] pb-3">
                Metode Pembayaran
            </h3>
            <div class="space-y-3">
                @forelse($paymentBreakdown as $pay)
                    <div class="flex items-center justify-between p-3 rounded-xl bg-[#faf7f0] border border-[#eee7d8]">
                        <span class="font-bold text-xs uppercase text-[#262017]">{{ $pay->method }}</span>
                        <div class="text-right">
                            <span class="text-xs font-serif-classic font-bold text-[#262017] block">
                                Rp {{ number_format($pay->total_amount, 0, ',', '.') }}
                            </span>
                            <span class="text-[10px] text-[#8a7f70]">{{ $pay->total_count }} transaksi</span>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-[#998f80]">Belum ada data pembayaran.</div>
                @endforelse
            </div>
        </div>

        <div class="lg:col-span-2 bg-white rounded-2xl border border-[#e5dfd3] shadow-xs p-6 space-y-4">
            <h3 class="font-serif-classic font-bold text-base text-[#262017] border-b border-[#f0ebd9] pb-3">
                10 Menu Paling Laris
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-[#f0ebd9] text-[#8a7f70] font-bold uppercase tracking-wider">
                            <th class="py-2.5 px-3">Menu</th>
                            <th class="py-2.5 px-3 text-center">Porsi Terjual</th>
                            <th class="py-2.5 px-3 text-right">Total Nilai Penjualan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#f7f4ed]">
                        @forelse($topProducts as $item)
                            <tr class="hover:bg-[#faf7f0] transition">
                                <td class="py-3 px-3">
                                    <p class="font-bold text-[#262017]">{{ $item->product_name_snapshot }}</p>
                                    <span class="font-mono text-[10px] text-[#8c8273]">{{ $item->sku_snapshot }}</span>
                                </td>
                                <td class="py-3 px-3 text-center font-bold text-[#262017]">
                                    {{ number_format($item->total_qty) }}
                                </td>
                                <td class="py-3 px-3 text-right font-medium text-[#262017]">
                                    Rp {{ number_format($item->total_revenue, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-8 text-center text-xs text-[#998f80]">
                                    Belum ada menu terjual.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Detailed Sales Transactions Table -->
    <div class="bg-white rounded-2xl border border-[#e5dfd3] shadow-xs overflow-hidden">
        <div class="p-6 border-b border-[#f0ebd9]">
            <h3 class="font-serif-classic font-bold text-base text-[#262017]">Rincian Transaksi Penjualan</h3>
            <p class="text-xs text-[#736858]">Daftar seluruh transaksi yang diselesaikan pada periode yang dipilih.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#faf7f0] border-b border-[#eee7d8] text-[#8a7f70] font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-6">No. Transaksi</th>
                        <th class="py-3.5 px-6">Waktu Transaksi</th>
                        <th class="py-3.5 px-6">Kasir Bertugas</th>
                        <th class="py-3.5 px-6 text-center">Metode Bayar</th>
                        <th class="py-3.5 px-6 text-right">Nilai Transaksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f7f4ed]">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-[#faf7f0] transition">
                            <td class="py-4 px-6 font-mono font-bold text-[#8a681c]">
                                {{ $sale->transaction_number }}
                            </td>
                            <td class="py-4 px-6 text-[#52493d]">
                                {{ $sale->transaction_date->translatedFormat('d M Y, H:i') }} WIB
                            </td>
                            <td class="py-4 px-6 font-semibold text-[#262017]">
                                {{ $sale->user->name ?? 'Kasir' }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-block uppercase font-bold text-[10px] px-2.5 py-0.5 rounded-full bg-stone-100 text-stone-700 border border-stone-200">
                                    {{ $sale->payment->method ?? '-' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right font-serif-classic font-bold text-[#262017] text-sm">
                                Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-xs text-[#998f80]">
                                Tidak ada transaksi penjualan pada rentang tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($sales->hasPages())
            <div class="p-4 border-t border-[#f0ebd9]">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    function applyOwnerPreset(startDate, endDate) {
        var startInput = document.getElementById('owner_start_date');
        var endInput = document.getElementById('owner_end_date');
        var form = document.getElementById('owner-report-filter-form');
        if (startInput && endInput && form) {
            startInput.value = startDate;
            endInput.value = endDate;
            form.submit();
        }
    }
</script>
@endsection
