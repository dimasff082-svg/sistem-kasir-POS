@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('cashier.open_bills.index') }}" class="text-xs font-bold text-amber-700 hover:underline">
                    &larr; Kembali ke Daftar Open Bill
                </a>
            </div>
            <h2 class="text-2xl font-black text-slate-800 tracking-tight mt-1">
                Rincian Tagihan: {{ $openBill->table_number }} ({{ $openBill->bill_number }})
            </h2>
            <p class="text-xs text-slate-500">
                Dibuka oleh {{ $openBill->opener->name ?? 'Kasir' }} pada {{ $openBill->opened_at->format('d/m/Y H:i') }} WIB
            </p>
        </div>

        <div>
            @if($openBill->isOpen())
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    Sesi Open Bill Aktif
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Sudah Dilunasi & Ditutup
                </span>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center gap-3 text-sm">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-2xl text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Daftar Pesanan Pelanggan di Open Bill Ini -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm">Daftar Transaksi Pesanan Pelanggan (Meja {{ $openBill->table_number }})</h3>
            <span class="text-xs font-semibold text-slate-400">{{ $openBill->sales->count() }} pesanan tergabung</span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($openBill->sales as $sale)
                <div class="p-6 space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div class="flex items-center gap-3">
                            <span class="font-mono font-bold text-amber-800 text-sm">{{ $sale->transaction_number }}</span>
                            <span class="text-xs text-slate-400">&bull; {{ $sale->transaction_date->format('H:i') }} WIB</span>
                            @if($sale->customer_name)
                                <span class="text-xs font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">{{ $sale->customer_name }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-semibold {{ $sale->isPaid() ? 'text-emerald-700 bg-emerald-50' : 'text-rose-700 bg-rose-50' }} px-2.5 py-1 rounded-full">
                                {{ $sale->paymentStatusLabel() }}
                            </span>
                            <span class="font-black text-slate-900 text-sm">
                                Rp {{ number_format($sale->total_amount, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="bg-slate-50 rounded-2xl p-3.5 space-y-1.5 text-xs">
                        @foreach($sale->items as $item)
                            <div class="flex justify-between items-center text-slate-700">
                                <span>{{ $item->quantity }}x {{ $item->product_name_snapshot }}</span>
                                <span class="font-bold text-slate-900">Rp {{ number_format($item->line_total, 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="py-10 text-center text-slate-400 text-xs">
                    Belum ada pesanan yang masuk pada sesi Open Bill ini.
                </div>
            @endforelse
        </div>

        <!-- Total Akumulasi Tagihan -->
        <div class="p-6 bg-amber-50/50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-amber-900 block">Total Tagihan Open Bill</span>
                <p class="text-xs text-slate-500">Akumulasi seluruh pesanan meja yang belum dilunasi.</p>
            </div>
            <div class="text-3xl font-black text-slate-900">
                Rp {{ number_format($openBill->total_amount, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <!-- Form Pelunasan Open Bill (Hanya jika masih Open) -->
    @if($openBill->isOpen() && $openBill->total_amount > 0)
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
            <h3 class="font-black text-slate-900 text-lg mb-1">Konfirmasi Pembayaran & Tutup Tagihan Meja</h3>
            <p class="text-xs text-slate-500 mb-6">Pilih metode pembayaran untuk melunasi seluruh tagihan di meja {{ $openBill->table_number }}.</p>

            <form action="{{ route('cashier.open_bills.confirm_payment', $openBill->id) }}" method="POST" class="space-y-5">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-2">Metode Pembayaran</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="cash" checked class="peer sr-only" onchange="toggleBillTendered(true)">
                            <div class="p-3 rounded-2xl border-2 border-slate-200 text-center text-xs font-bold peer-checked:border-amber-600 peer-checked:bg-amber-50 peer-checked:text-amber-800 transition">
                                Tunai
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="qris" class="peer sr-only" onchange="toggleBillTendered(false)">
                            <div class="p-3 rounded-2xl border-2 border-slate-200 text-center text-xs font-bold peer-checked:border-amber-600 peer-checked:bg-amber-50 peer-checked:text-amber-800 transition">
                                QRIS
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payment_method" value="transfer" class="peer sr-only" onchange="toggleBillTendered(false)">
                            <div class="p-3 rounded-2xl border-2 border-slate-200 text-center text-xs font-bold peer-checked:border-amber-600 peer-checked:bg-amber-50 peer-checked:text-amber-800 transition">
                                Transfer
                            </div>
                        </label>
                    </div>
                </div>

                <div id="billTenderedWrapper" class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700">Nominal Uang Diterima (Rp) *</label>
                    <input type="text" id="billInputTendered" inputmode="numeric"
                        placeholder="Ketik nominal uang yang diterima dari pelanggan (contoh: 60.000)..."
                        oninput="handleBillTenderedInput(this)"
                        class="w-full bg-slate-50 border border-slate-300 rounded-2xl px-4 py-3 text-lg font-black text-slate-900 focus:ring-2 focus:ring-amber-500 focus:outline-none">
                    <input type="hidden" id="rawBillAmountTendered" name="amount_tendered" value="">

                    <div class="flex items-center justify-between text-xs pt-1">
                        <span class="text-slate-500 font-semibold">Uang Kembalian:</span>
                        <span id="billLabelChange" class="font-black text-emerald-600 text-base">Rp 0</span>
                    </div>

                    <div class="pt-1.5 space-y-2">
                        <button type="button" onclick="setBillExactAmount()" class="w-full py-2.5 px-4 rounded-xl bg-amber-100 hover:bg-amber-200 text-xs font-black text-amber-900 border border-amber-300 transition flex items-center justify-center gap-2 shadow-2xs">
                            <svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>Uang Pas (Rp {{ number_format($openBill->total_amount, 0, ',', '.') }})</span>
                        </button>
                        
                        <div class="grid grid-cols-4 gap-1.5 text-xs font-bold text-slate-700">
                            <button type="button" onclick="setBillPresetAmount(10000)" class="py-2 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">10.000</button>
                            <button type="button" onclick="setBillPresetAmount(20000)" class="py-2 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">20.000</button>
                            <button type="button" onclick="setBillPresetAmount(50000)" class="py-2 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">50.000</button>
                            <button type="button" onclick="setBillPresetAmount(100000)" class="py-2 rounded-xl bg-slate-100 hover:bg-slate-200 border border-slate-200 transition">100.000</button>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="submit" class="w-full sm:w-auto px-8 py-3.5 rounded-2xl bg-amber-600 hover:bg-amber-700 text-white text-sm font-extrabold shadow-lg shadow-amber-600/20 transition transform hover:scale-105 active:scale-95">
                        Lunasi Tagihan Meja & Selesaikan Open Bill
                    </button>
                </div>
            </form>
        </div>

        <script>
            const billTotal = {{ (float)$openBill->total_amount }};

            function getCleanNumericValue(str) {
                if (!str) return 0;
                const cleaned = String(str).replace(/[^\d]/g, '');
                return cleaned ? parseInt(cleaned, 10) : 0;
            }

            function handleBillTenderedInput(inputEl) {
                const raw = getCleanNumericValue(inputEl.value);
                if (raw > 0) {
                    inputEl.value = Number(raw).toLocaleString('id-ID');
                    document.getElementById('rawBillAmountTendered').value = raw;
                } else {
                    inputEl.value = '';
                    document.getElementById('rawBillAmountTendered').value = '';
                }
                calculateBillChange();
            }

            function toggleBillTendered(isCash) {
                const wrapper = document.getElementById('billTenderedWrapper');
                if (isCash) {
                    wrapper.classList.remove('hidden');
                    document.getElementById('billInputTendered').value = '';
                    document.getElementById('rawBillAmountTendered').value = '';
                    document.getElementById('billLabelChange').innerText = "Rp 0";
                } else {
                    wrapper.classList.add('hidden');
                    document.getElementById('billInputTendered').value = Number(billTotal).toLocaleString('id-ID');
                    document.getElementById('rawBillAmountTendered').value = billTotal;
                }
            }

            function setBillExactAmount() {
                document.getElementById('billInputTendered').value = Number(billTotal).toLocaleString('id-ID');
                document.getElementById('rawBillAmountTendered').value = billTotal;
                calculateBillChange();
            }

            function setBillPresetAmount(amount) {
                document.getElementById('billInputTendered').value = Number(amount).toLocaleString('id-ID');
                document.getElementById('rawBillAmountTendered').value = amount;
                calculateBillChange();
            }

            function calculateBillChange() {
                const val = getCleanNumericValue(document.getElementById('billInputTendered').value);
                const change = Math.max(0, val - billTotal);
                document.getElementById('billLabelChange').innerText = "Rp " + new Intl.NumberFormat('id-ID').format(change);
            }
        </script>
    @endif
</div>
@endsection
