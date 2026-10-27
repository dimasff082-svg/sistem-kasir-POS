@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="font-serif-classic text-2xl font-bold text-[#262017] tracking-tight">Riwayat Seluruh Transaksi</h2>
            <p class="text-xs text-[#786e60] mt-0.5 font-sans">Daftar rekaman seluruh penjualan toko yang diproses oleh kasir.</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-[#e5dfd3] shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)] p-4">
        <form action="{{ route('admin.transactions.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-3">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="No. Transaksi (TRX-...)"
                    class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3.5 py-2 text-xs text-[#262017] font-mono focus:ring-2 focus:ring-amber-500 focus:outline-none">
            </div>

            <div class="sm:col-span-3">
                <select name="user_id" class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <option value="">Semua Kasir</option>
                    @foreach($cashiers as $c)
                        <option value="{{ $c->id }}" {{ request('user_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2">
                <select name="payment_method" class="w-full bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-3 py-2 text-xs text-[#262017] focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <option value="">Semua Metode</option>
                    <option value="cash" {{ request('payment_method') === 'cash' ? 'selected' : '' }}>Tunai</option>
                    <option value="transfer" {{ request('payment_method') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                    <option value="qris" {{ request('payment_method') === 'qris' ? 'selected' : '' }}>QRIS</option>
                </select>
            </div>

            <div class="sm:col-span-2 flex gap-1.5">
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-1/2 bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-2 py-2 text-[11px] text-[#262017] focus:ring-2 focus:ring-amber-500 focus:outline-none">
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-1/2 bg-[#fcfbf9] border border-[#dcd4c5] rounded-xl px-2 py-2 text-[11px] text-[#262017] focus:ring-2 focus:ring-amber-500 focus:outline-none">
            </div>

            <div class="sm:col-span-2 flex gap-2">
                <button type="submit" class="flex-1 bg-[#262017] hover:bg-[#15120c] text-[#f7f5f0] text-xs font-semibold px-4 py-2 rounded-xl transition shadow-xs">
                    Saring
                </button>
                @if(request()->hasAny(['search', 'user_id', 'payment_method', 'date_from', 'date_to']))
                    <a href="{{ route('admin.transactions.index') }}" class="bg-[#f0ebd9] hover:bg-[#e4ddc8] text-[#5c5346] text-xs font-semibold px-3 py-2 rounded-xl transition flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Sales Table -->
    <div class="bg-white rounded-2xl border border-[#e5dfd3] shadow-[0_4px_20px_-4px_rgba(40,30,15,0.05)] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-[#faf7f0] border-b border-[#e5dfd3] text-[#786e60] text-xs font-bold uppercase tracking-wider font-serif-classic">
                        <th class="py-3.5 px-6">No. Transaksi</th>
                        <th class="py-3.5 px-6">Waktu Transaksi</th>
                        <th class="py-3.5 px-6">Kasir Bertugas</th>
                        <th class="py-3.5 px-6 text-center">Metode Bayar</th>
                        <th class="py-3.5 px-6 text-center">Jumlah Item</th>
                        <th class="py-3.5 px-6 text-right">Total Transaksi</th>
                        <th class="py-3.5 px-6 text-center">Status</th>
                        <th class="py-3.5 px-6 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#f0ebd9] font-medium">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-[#faf7f0]/70 transition">
                            <td class="py-4 px-6 font-mono text-xs font-bold text-[#8a681c]">
                                {{ $sale->transaction_number }}
                            </td>
                            <td class="py-4 px-6 text-xs text-[#786e60]">
                                {{ $sale->transaction_date->format('d/m/Y H:i') }}
                            </td>
                            <td class="py-4 px-6 text-xs font-bold text-[#262017]">
                                {{ $sale->user->name ?? 'Kasir' }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold bg-[#faf7f0] border border-[#e5dfd3] text-[#5c5346]">
                                    {{ $sale->payment?->methodLabel() ?? 'Tunai' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center text-xs font-bold text-[#5c5346]">
                                {{ $sale->items->sum('quantity') }}
                            </td>
                            <td class="py-4 px-6 text-right text-xs font-bold font-serif-classic text-[#1b4332]">
                                Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                    Selesai
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="inline-flex items-center gap-1.5 justify-center">
                                    <!-- Detail Transaksi (Eye Icon) -->
                                    <a href="{{ route('admin.transactions.show', $sale->id) }}"
                                        title="Lihat Detail Transaksi"
                                        aria-label="Lihat Detail Transaksi"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg bg-amber-50 text-[#8a681c] hover:bg-[#c59b27] hover:text-stone-950 border border-amber-300/80 transition shadow-2xs hover:scale-105 active:scale-95">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>

                                    <!-- Cetak Struk Digital (Receipt / Print Icon) -->
                                    <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank"
                                        title="Cetak Struk Digital"
                                        aria-label="Cetak Struk Digital"
                                        class="w-8 h-8 inline-flex items-center justify-center rounded-lg bg-emerald-50 text-emerald-800 hover:bg-[#1b4332] hover:text-emerald-100 border border-emerald-300 transition shadow-2xs hover:scale-105 active:scale-95">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-[#998f80] text-sm">
                                <div class="w-12 h-12 mx-auto mb-2 rounded-xl bg-[#faf7f0] border border-[#e5dfd3] flex items-center justify-center text-[#b8a994]">
                                    <i class="fa-solid fa-receipt text-lg"></i>
                                </div>
                                <p class="font-serif-classic font-bold text-[#262017]">Tidak ada transaksi ditemukan</p>
                                <p class="text-xs text-[#786e60] mt-0.5">Coba sesuaikan filter pencarian atau tanggal transaksi.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sales->hasPages())
            <div class="p-4 border-t border-[#f0ebd9] bg-[#faf7f0]">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
