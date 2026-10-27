@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Riwayat Pergerakan Stok (Mutasi)</h2>
            <p class="text-sm text-slate-500 mt-0.5">Jejak audit lengkap setiap penambahan, pengurangan, penjualan, dan penyesuaian persediaan.</p>
        </div>
        <a href="{{ route('admin.stock.index') }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-xl shadow-xs transition">
            &larr; Kembali ke Daftar Stok
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
        <form action="{{ route('admin.stock.movements') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3">
            <div class="sm:col-span-4 relative">
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari nama barang atau SKU..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="sm:col-span-3">
                <select name="movement_type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Semua Jenis Mutasi</option>
                    <option value="initial" {{ request('movement_type') === 'initial' ? 'selected' : '' }}>Stok Awal</option>
                    <option value="purchase_in" {{ request('movement_type') === 'purchase_in' ? 'selected' : '' }}>Barang Masuk</option>
                    <option value="sale_out" {{ request('movement_type') === 'sale_out' ? 'selected' : '' }}>Penjualan (Kasir)</option>
                    <option value="adjustment" {{ request('movement_type') === 'adjustment' ? 'selected' : '' }}>Penyesuaian Fisik</option>
                    <option value="damaged" {{ request('movement_type') === 'damaged' ? 'selected' : '' }}>Barang Rusak</option>
                </select>
            </div>

            <div class="sm:col-span-3 flex gap-2">
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-1/2 bg-slate-50 border border-slate-200 rounded-xl px-2 py-2 text-xs text-slate-800">
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-1/2 bg-slate-50 border border-slate-200 rounded-xl px-2 py-2 text-xs text-slate-800">
            </div>

            <div class="sm:col-span-2 flex gap-2">
                <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold px-4 py-2 rounded-xl transition">
                    Saring
                </button>
                @if(request()->hasAny(['search', 'movement_type', 'date_from', 'date_to']))
                    <a href="{{ route('admin.stock.movements') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold px-3 py-2 rounded-xl transition flex items-center justify-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Movements Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-6">Waktu</th>
                        <th class="py-3.5 px-6">Barang & SKU</th>
                        <th class="py-3.5 px-6">Jenis Mutasi</th>
                        <th class="py-3.5 px-6 text-center">Perubahan</th>
                        <th class="py-3.5 px-6 text-center">Stok (Sebelum &rarr; Sesudah)</th>
                        <th class="py-3.5 px-6">Penanggung Jawab</th>
                        <th class="py-3.5 px-6">Alasan / Referensi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($movements as $m)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-4 px-6 text-xs text-slate-500 whitespace-nowrap">
                                {{ $m->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-bold text-slate-900">{{ $m->product->name ?? 'Produk Dihapus' }}</div>
                                <div class="font-mono text-xs text-slate-400">{{ $m->product->sku ?? '-' }}</div>
                            </td>
                            <td class="py-4 px-6">
                                @php
                                    $badgeClass = match($m->movement_type) {
                                        'initial' => 'bg-slate-100 text-slate-700',
                                        'purchase_in' => 'bg-emerald-100 text-emerald-800',
                                        'sale_out' => 'bg-blue-100 text-blue-800',
                                        'damaged' => 'bg-rose-100 text-rose-800',
                                        default => 'bg-indigo-100 text-indigo-800',
                                    };
                                @endphp
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-bold {{ $badgeClass }}">
                                    {{ $m->typeLabel() }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center font-extrabold text-sm {{ $m->quantity_change > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $m->quantity_change > 0 ? '+' : '' }}{{ $m->quantity_change }}
                            </td>
                            <td class="py-4 px-6 text-center text-xs font-mono text-slate-600">
                                {{ $m->stock_before }} &rarr; <span class="font-bold text-slate-900">{{ $m->stock_after }}</span>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-700">
                                {{ $m->user->name ?? 'Sistem' }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 max-w-xs truncate">
                                {{ $m->reason ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400 text-sm">
                                Belum ada riwayat pergerakan stok yang tercatat.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($movements->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
