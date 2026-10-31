@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-serif-classic font-bold text-[#262017] tracking-tight">Tambah Menu Baru</h2>
            <p class="text-xs text-[#736858] mt-0.5">Daftarkan menu makanan atau minuman baru untuk mulai dipesan pelanggan.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3.5 py-2 rounded-xl transition">
            &larr; Kembali
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-8">
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <!-- 1. Kode Menu & 2. Kategori Menu -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kode Menu *</label>
                    <input type="text" name="sku" value="{{ old('sku') }}" required placeholder="Contoh: MKN-001 / MNM-001"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm uppercase font-mono font-bold focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                    @error('sku')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori Menu</label>
                    <select name="category_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                        <option value="">-- Tanpa Kategori --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- 3. Nama Menu & 4. Harga Menu -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2 border-t border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Menu *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Nasi Goreng Spesial / Kopi Latte"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                    @error('name')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Harga Menu (Rp) *</label>
                    <input type="number" name="selling_price" value="{{ old('selling_price') }}" min="0" step="500" required placeholder="Contoh: 25000"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm font-bold text-slate-900 focus:ring-2 focus:ring-[#c59b27] focus:outline-none">
                    @error('selling_price')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Deskripsi Menu -->
            <div class="pt-2 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi Menu</label>
                <textarea name="description" rows="3" placeholder="Tuliskan deskripsi menu, bahan utama, rasa, atau catatan khusus untuk membantu kasir menjelaskan menu ke pelanggan..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-sm focus:ring-2 focus:ring-[#c59b27] focus:outline-none">{{ old('description') }}</textarea>
                <p class="text-[11px] text-slate-400 mt-1">Deskripsi ini akan dibaca oleh kasir saat pelanggan menanyakan detail sajian menu.</p>
                @error('description')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- 5. Foto Menu -->
            <div class="pt-2 border-t border-slate-100 space-y-3">
                <label class="block text-xs font-bold text-slate-700">Foto Menu</label>
                <div class="flex items-center gap-5">
                    <div id="image-preview-wrapper" class="w-24 h-24 rounded-2xl bg-slate-100 border-2 border-dashed border-slate-300 flex items-center justify-center overflow-hidden shrink-0">
                        <svg id="image-placeholder-icon" class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <img id="image-preview" src="#" alt="Preview" class="hidden w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/jpg,image/webp"
                            onchange="previewMenuImage(this)"
                            class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#f6f2e9] file:text-[#362f25] hover:file:bg-[#ece4d3] file:cursor-pointer cursor-pointer border border-slate-200 rounded-xl p-1 bg-slate-50">
                        <p class="text-[11px] text-slate-400 mt-1.5">Format JPG, PNG, atau WEBP. Maksimal 2MB. Foto akan ditampilkan pada layar pemesanan pelanggan.</p>
                        @error('image')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                <a href="{{ route('admin.products.index') }}" class="px-5 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Batal
                </a>
                <button type="submit" class="bg-[#c59b27] hover:bg-[#a98119] text-stone-950 font-bold text-xs px-6 py-2.5 rounded-xl shadow-md transition transform active:scale-95">
                    Simpan Menu Baru
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function previewMenuImage(input) {
        const preview = document.getElementById('image-preview');
        const icon = document.getElementById('image-placeholder-icon');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                icon.classList.add('hidden');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
