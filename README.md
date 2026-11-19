# Sistem Informasi Toko Delima

Aplikasi web lokal untuk pengelolaan stok, pembelian, dan transaksi kasir. Dibangun dengan Laravel 12, PHP 8.2+ (ekstensi `bcmath`), MySQL, Blade, dan Vite.

## Menjalankan pertama kali

Lihat [Panduan Demo Website](./PANDUAN-DEMO.md) untuk langkah menjalankan demo lengkap di Laragon, akun, serta alur presentasi.

1. Pastikan PHP, Composer, Node.js/npm, dan MySQL tersedia. Buka terminal di folder proyek ini.
2. Buat basis data kosong bernama `toko_delima_mpsi` di phpMyAdmin atau MySQL:

   ```sql
   CREATE DATABASE toko_delima_mpsi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. Siapkan konfigurasi:

   ```powershell
   Copy-Item .env.example .env
   ```

   Atur `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di `.env` sesuai MySQL lokal Anda.
4. Pasang dependensi, buat kunci aplikasi, lalu buat tabel dan akun/data demo:

   ```powershell
   composer install
   npm install
   php artisan key:generate
   php artisan migrate --seed
   npm run build
   ```

   Seeder membuat tiga akun demo (`owner`, `supervisor`, `kasir`). Kata sandi acak ditampilkan di terminal kecuali `DEMO_USER_PASSWORD` diatur; nilai yang diatur juga memperbarui kata sandi akun demo yang sudah ada saat seeder dijalankan. Jalankan `php artisan migrate:fresh --seed` hanya jika ingin menghapus seluruh data dan mengulang demo.
5. Jalankan server lokal:

   ```powershell
   php artisan serve --host=127.0.0.1 --port=8000
   ```

6. Buka <http://127.0.0.1:8000> dan masuk menggunakan akun demo.

## Role dan batas akses

- **Owner**: seluruh menu, termasuk laporan pembelian/penjualan dan transaksi kasir.
- **Supervisor**: data barang, supplier, pembelian, kartu stok, dan laporan stok.
- **Kasir**: transaksi dan struk; pencarian transaksi hanya mengirim nama, kode, satuan, harga jual, dan stok. Harga beli dan endpoint laporan/master data ditolak di sisi server.

## Alur yang tersedia

- Barang baru dimulai dengan stok 0; stok hanya berubah dari pembelian/penjualan.
- Pembelian disimpan dalam satu transaksi basis data, menambah stok dan membuat mutasi masuk.
- Kasir mencari barang, membuat keranjang, membayar tunai/transfer, melihat kembalian (tunai), dan mencetak struk.
- Penjualan melebihi stok yang tersedia ditolak. Nomor struk mengikuti `PJ-DDMMYY-XXX`.
- Data transaksi dan kartu stok yang telah tersimpan tidak bisa diedit/dihapus di versi pertama. Retur dan pembatalan belum termasuk.
- Nilai kategori dan satuan dapat diisi sebagai teks. Setiap variasi ukuran/warna dicatat sebagai barang/SKU tersendiri.

Data supplier, barang, pembelian, dan kata sandi seeder yang tampil di terminal adalah **data demo**, bukan data Toko Delima yang sebenarnya.

Seeder memakai data relevan dari workbook `Data_Dummy_Toko_Delima_Axelor_April_2026_v2 (1).xlsx`: 54 barang dan 16 supplier dari sheet **Master Produk** dan **Master Supplier**, serta 100 transaksi tanpa diskon yang tersebar sepanjang 1–20 April 2026 dari sheet **Transaksi Penjualan**. Sampel transaksi dipilih merata dari lebih dari 8.500 baris yang memenuhi syarat; transaksi yang mengandung diskon tidak dimasukkan karena skema penjualan belum menyimpan diskon per item. Stok awal menggunakan kolom **Stok Awal April** dan dikurangi hanya oleh 100 transaksi sampel. Pembelian historis, ringkasan, promo, dan data karyawan tidak dimuat. Barang demo lama `BRG-DEMO-*` dibersihkan saat data workbook pertama kali dimuat bila belum memiliki transaksi penjualan.

## Validasi

```powershell
php artisan test
npm run build
```
