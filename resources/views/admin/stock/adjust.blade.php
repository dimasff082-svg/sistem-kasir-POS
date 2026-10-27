@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight">Penyesuaian Stok Fisik</h2>
            <p class="text-sm text-slate-500 mt-0.5">Catat perubahan persediaan dengan alasan yang sah dan dapat diaudit.</p>
        </div>
        <a href="{{ route('admin.stock.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3.5 py-2 rounded-xl transition">
            &larr; Kembali
        </a>
    </div>

    <!-- Product Info Card -->
    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 text-white rounded-2xl p-6 shadow-md">
        <div class="flex items-center justify-between">
            <div>
                <span class="text-xs font-bold text-indigo-400 font-mono tracking-wider">{{ $product->sku }}</span>
                <h3 class="text-xl font-extrabold text-white mt-1">{{ $product->name }}</h3>
                <span class="text-xs text-slate-400">Kategori: {{ $product->category->name ?? 'Tanpa Kategori' }}</span>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-400 block">Stok Saat Ini</span>
                <span class="text-3xl font-black text-white" id="current-stock-val">{{ $product->stock }}</span>
                <span class="text-xs text-indigo-300 font-medium"> {{ $product->unit }}</span>
            </div>
        </div>
    </div>

    <!-- Adjustment Form -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-8">
        <form action="{{ route('admin.stock.adjust.post', $product->id) }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Jenis Penyesuaian *</label>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <label class="border-2 border-slate-200 rounded-xl p-3.5 flex flex-col cursor-pointer hover:border-indigo-500 has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/50 transition">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="adjustment_type" value="add" checked onchange="calcPreview()" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="text-xs font-bold text-slate-800">Tambah Masuk (+)</span>
                        </div>
                        <span class="text-[11px] text-slate-500 mt-1">Penerimaan barang atau restock dari supplier.</span>
                    </label>

                    <label class="border-2 border-slate-200 rounded-xl p-3.5 flex flex-col cursor-pointer hover:border-indigo-500 has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/50 transition">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="adjustment_type" value="subtract" onchange="calcPreview()" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="text-xs font-bold text-slate-800">Kurang Keluar (-)</span>
                        </div>
                        <span class="text-[11px] text-slate-500 mt-1">Barang rusak, kedaluwarsa, atau hilang.</span>
                    </label>

                    <label class="border-2 border-slate-200 rounded-xl p-3.5 flex flex-col cursor-pointer hover:border-indigo-500 has-[:checked]:border-indigo-600 has-[:checked]:bg-indigo-50/50 transition">
                        <div class="flex items-center gap-2">
                            <input type="radio" name="adjustment_type" value="set" onchange="calcPreview()" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="text-xs font-bold text-slate-800">Set Nilai Nyata (=)</span>
                        </div>
                        <span class="text-[11px] text-slate-500 mt-1">Hasil stok opname / hitung fisik terkini.</span>
                    </label>
                </div>
            </div>

            <div>
                <label id="amount-label" class="block text-xs font-bold text-slate-700 mb-1.5">Jumlah Barang *</label>
                <input type="number" id="amount-input" name="amount" value="{{ old('amount', 1) }}" min="0" required oninput="calcPreview()"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-base font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500">
            </div>

            <!-- Preview Kalkulasi -->
            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between text-xs">
                <span class="text-slate-500 font-medium">Estimasi Stok Akhir Setelah Penyesuaian:</span>
                <span id="preview-stock" class="text-base font-black text-indigo-600">
                    {{ $product->stock + 1 }} {{ $product->unit }}
                </span>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Alasan Penyesuaian Stok *</label>
                <input type="text" name="reason" value="{{ old('reason') }}" required
                    placeholder="Contoh: Penerimaan 2 dus baru / Stok opname bulanan / Kemasan bocor"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500">
                <p class="text-[11px] text-slate-400 mt-1">Wajib diisi sebagai rekaman audit penanggung jawab stok.</p>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.stock.index') }}" class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </a>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs px-6 py-2.5 rounded-xl shadow-md shadow-indigo-600/20 transition transform active:scale-95">
                    Simpan Perubahan Stok
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const baseStock = {{ $product->stock }};
    const unit = '{{ $product->unit }}';

    function calcPreview() {
        const type = document.querySelector('input[name="adjustment_type"]:checked').value;
        const amount = parseInt(document.getElementById('amount-input').value) || 0;
        let finalStock = baseStock;

        if (type === 'add') {
            finalStock = baseStock + amount;
            document.getElementById('amount-label').innerText = 'Jumlah Barang yang Ditambahkan (+) *';
        } else if (type === 'subtract') {
            finalStock = baseStock - amount;
            document.getElementById('amount-label').innerText = 'Jumlah Barang yang Dikurangkan (-) *';
        } else if (type === 'set') {
            finalStock = amount;
            document.getElementById('amount-label').innerText = 'Nilai Stok Akhir yang Ditetapkan (=) *';
        }

        const previewEl = document.getElementById('preview-stock');
        previewEl.innerText = finalStock + ' ' + unit;

        if (finalStock < 0) {
            previewEl.classList.remove('text-indigo-600');
            previewEl.classList.add('text-rose-600');
            previewEl.innerText += ' (Peringatan: Stok tidak boleh kurang dari nol!)';
        } else {
            previewEl.classList.remove('text-rose-600');
            previewEl.classList.add('text-indigo-600');
        }
    }

    calcPreview();
</script>
@endsection
