@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Manajemen Stok Barang</h2>
            <p class="text-sm text-slate-500 mt-0.5">Pantau persediaan fisik toko dan lakukan penyesuaian dengan alasan tercatat.</p>
        </div>
        <a href="{{ route('admin.stock.movements') }}" class="inline-flex items-center gap-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 text-xs font-semibold px-4 py-2.5 rounded-xl shadow-xs transition">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Lihat Riwayat Mutasi Stok
        </a>
    </div>

    <!-- Filter Buttons & Search -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4">
        <form action="{{ route('admin.stock.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Cari barang atau SKU..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.stock.index') }}"
                    class="px-3 py-2 rounded-xl text-xs font-semibold transition {{ !request('filter') ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    Semua Barang
                </a>
                <a href="{{ route('admin.stock.index', ['filter' => 'low_stock']) }}"
                    class="px-3 py-2 rounded-xl text-xs font-semibold transition {{ request('filter') === 'low_stock' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                    Stok Menipis
                </a>
                <a href="{{ route('admin.stock.index', ['filter' => 'out_of_stock']) }}"
                    class="px-3 py-2 rounded-xl text-xs font-semibold transition {{ request('filter') === 'out_of_stock' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100' }}">
                    Stok Habis (0)
                </a>
            </div>
        </form>
    </div>

    <!-- Stock Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-200 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <th class="py-3.5 px-6">SKU / Kode</th>
                        <th class="py-3.5 px-6">Nama Barang</th>
                        <th class="py-3.5 px-6">Kategori</th>
                        <th class="py-3.5 px-6 text-center">Batas Minimum</th>
                        <th class="py-3.5 px-6 text-center">Stok Fisik Saat Ini</th>
                        <th class="py-3.5 px-6 text-center">Kondisi</th>
                        <th class="py-3.5 px-6 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-4 px-6 font-mono text-xs font-bold text-slate-800">
                                {{ $product->sku }}
                            </td>
                            <td class="py-4 px-6 text-slate-900 font-bold">
                                {{ $product->name }}
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-600">
                                {{ $product->category->name ?? '-' }}
                            </td>
                            <td class="py-4 px-6 text-center text-xs text-slate-500">
                                {{ $product->minimum_stock }} {{ $product->unit }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="text-base font-extrabold {{ $product->stock <= 0 ? 'text-rose-600' : ($product->isLowStock() ? 'text-amber-600' : 'text-slate-900') }}">
                                    {{ $product->stock }}
                                </span>
                                <span class="text-xs text-slate-400 font-normal"> {{ $product->unit }}</span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                @if($product->stock <= 0)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-800">
                                        Stok Habis
                                    </span>
                                @elseif($product->isLowStock())
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                        Stok Menipis
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                                        Aman
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a href="{{ route('admin.stock.adjust', $product->id) }}"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Sesuaikan Stok
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400 text-sm">
                                Tidak ada data stok barang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
