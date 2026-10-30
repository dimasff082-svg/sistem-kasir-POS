# Revisi SIstem

Saya ingin melakukan perubahan pada tugas dan hak akses role Kasir dalam sistem Cafe & Restaurant yang sedang dikembangkan.

## 1. Perubahan Tugas Kasir

Sederhanakan tugas Kasir. Sekarang Kasir **tidak lagi bertugas memilih menu, membuat pesanan, atau menginput pesanan untuk pelanggan**.

Seluruh proses pemilihan dan pemesanan makanan dilakukan secara mandiri oleh pelanggan melalui QR Code yang terdapat pada setiap meja.

Tugas utama Kasir sekarang adalah:
- Melihat daftar pesanan yang masuk dari pelanggan.
- Melihat ID meja, detail pesanan, dan total tagihan.
- Melihat status pembayaran setiap pesanan.
- Mengonfirmasi apakah pesanan **Sudah Dibayar (Lunas)** atau **Belum Dibayar**.
- Mengelola Open Bill untuk meja jika fitur tersebut digunakan.

## 2. Alur Kerja Baru

1. Pelanggan duduk di meja dan memindai QR Code.
2. Pelanggan memilih menu dan mengirim pesanan secara mandiri.
3. Sistem secara otomatis menyimpan pesanan beserta ID meja.
4. Pesanan masuk ke dashboard Kasir dan Dapur.
5. Status pembayaran awal adalah **Belum Dibayar**.
6. Dapur menerima pesanan dan memproses makanan.
7. Kasir memeriksa pembayaran pelanggan.
8. Jika pembayaran telah diterima, Kasir menekan tombol **Konfirmasi Pembayaran**.
9. Sistem memperbarui status pembayaran menjadi **Lunas**.
10. Status pembayaran yang diperbarui tersimpan di database dan ditampilkan secara konsisten.

## 3. Tampilan Dashboard Kasir

Buat dashboard Kasir yang sederhana dengan tabel:

| ID Pesanan | Meja | Total | Pembayaran | Aksi |
|---|---|---|---|---|
| ORD001 | M001 | Rp45.000 | Belum Dibayar | Konfirmasi |
| ORD002 | M002 | Rp30.000 | Lunas | Detail |
| ORD003 | M003 | Rp75.000 | Belum Dibayar | Konfirmasi |

Kasir tidak membutuhkan fitur:
- Tambah pesanan.
- Pilih menu pelanggan.
- Tambah menu ke keranjang.
- Mengubah jumlah menu yang dipesan.
- Membuat pesanan atas nama pelanggan.

## 4. Penyesuaian Fitur Open Bill

Fitur Open Bill tetap tersedia, tetapi digunakan khusus untuk pengelolaan tagihan.

- Kasir dapat membuka Open Bill untuk meja tertentu.
- Jika Open Bill aktif, pesanan pelanggan pada sesi meja tersebut digabungkan ke satu tagihan.
- Kasir dapat melihat total tagihan yang terkumpul.
- Setelah pembayaran diterima, Kasir mengonfirmasi pembayaran.
- Open Bill ditutup setelah tagihan dilunasi.
- Jika Open Bill tidak aktif, setiap pesanan memiliki status pembayaran masing-masing.

Kasir tidak boleh menambahkan menu atau mengubah isi pesanan melalui fitur Open Bill.

## 5. Pisahkan Status Pesanan dan Pembayaran

Gunakan dua status berbeda:

**Status Pesanan (dikelola Dapur):**
- Menunggu
- Diproses
- Selesai

**Status Pembayaran (dikelola Kasir):**
- Belum Dibayar
- Lunas

Perubahan status pesanan oleh Dapur tidak boleh otomatis mengubah status pembayaran.

Setiap konfirmasi pembayaran harus tercatat, termasuk ID Kasir dan waktu konfirmasi. Validasi hak akses harus dilakukan di backend.

## 6. Instruksi Penting untuk AI Agent

Perbarui implementasi role Kasir berdasarkan instruksi terbaru ini.

- Hapus fungsi pemilihan menu dan pembuatan pesanan dari dashboard Kasir.
- Pertahankan halaman daftar pesanan dan fitur konfirmasi pembayaran.
- Pertahankan fitur Open Bill dengan penyesuaian tugas Kasir.
- Pastikan pesanan hanya dibuat oleh pelanggan melalui halaman QR Code.
- Perbarui frontend, backend, database, routing, dan hak akses jika diperlukan.
- Jangan mengubah fungsi role Admin, Pemilik, Dapur, dan Pelanggan yang sudah ditentukan sebelumnya.
- Pastikan seluruh alur berjalan dan terhubung dengan database.

**Instruksi ini menggantikan seluruh ketentuan tugas Kasir sebelumnya yang bertentangan dengan perubahan terbaru.**