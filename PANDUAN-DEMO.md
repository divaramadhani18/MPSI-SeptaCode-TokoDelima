# Panduan Menjalankan Demo Website Toko Delima

Panduan ini untuk menjalankan aplikasi di Windows dengan Laragon. Aplikasi dibuka di komputer lokal, bukan di internet.

## 1. Persiapan

1. Buka Laragon, lalu klik **Start All**. Pastikan MySQL berjalan.
2. Buka terminal/PowerShell di folder proyek:

   ```powershell
   cd C:\laragon\www\toko_delima_mpsi
   ```

3. Pastikan `.env` tersedia. Jangan menimpa `.env` yang sudah ada. Jika belum ada, salin konfigurasi contoh:

   ```powershell
   Copy-Item .env.example .env
   ```

4. Pastikan konfigurasi database di `.env` sesuai MySQL Laragon:

   ```dotenv
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=toko_delima_mpsi
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   Buat database kosong bernama `toko_delima_mpsi` melalui phpMyAdmin jika belum tersedia. Jika MySQL Anda memakai kata sandi, isi `DB_PASSWORD` sesuai konfigurasi setempat.

## 2. Siapkan akun demo dan data contoh

Tambahkan baris berikut di `.env` untuk memakai kata sandi demo yang mudah diingat:

```dotenv
DEMO_USER_PASSWORD=DelimaDemo2026!
```

Kata sandi ini hanya contoh untuk komputer demo lokal. Jangan gunakan untuk sistem produksi. Menjalankan seeder dengan pengaturan ini akan menetapkan kata sandi tersebut untuk akun demo `owner`, `supervisor`, dan `kasir`, termasuk bila akun itu sudah pernah dibuat.

Jalankan perintah berikut satu per satu:

```powershell
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
```

Jika `APP_KEY` sudah terisi di `.env`, Anda tidak perlu menjalankan `php artisan key:generate` lagi. Jika dependensi sudah tersedia, `composer install` dan `npm install` boleh dilewati.

Seeder memuat 54 barang, 16 supplier, dan 100 transaksi penjualan sampel dari workbook April 2026. Data transaksi dipilih merata sepanjang 1–20 April dan tanpa diskon agar nominal penjualan sesuai dengan fitur aplikasi. Stok demo dimulai dari stok awal workbook dikurangi oleh transaksi yang dimuat; karena hanya sampel transaksi yang dipakai, stok tidak dimaksudkan sebagai rekonstruksi stok akhir April. Data karyawan, promo, dan pembelian historis workbook tidak dimuat.

## 3. Jalankan website

Di terminal pada folder proyek, jalankan:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

Biarkan terminal ini tetap terbuka selama demo. Buka browser ke <http://127.0.0.1:8000>.

## 4. Masuk ke aplikasi

| Peran | Nama pengguna | Kata sandi |
|---|---|---|
| Pemilik | `owner` | `DelimaDemo2026!` |
| Supervisor | `supervisor` | `DelimaDemo2026!` |
| Kasir | `kasir` | `DelimaDemo2026!` |

Kata sandi pada tabel berlaku jika Anda menggunakan nilai `DEMO_USER_PASSWORD` yang sama pada langkah persiapan. Jika baris tersebut tidak diatur, seeder membuat kata sandi acak dan menampilkannya di terminal saat akun pertama kali dibuat.

## 5. Alur demo yang disarankan

Mulai dengan akun **owner** agar seluruh menu terlihat:

1. **Ringkasan toko** — tunjukkan jumlah barang aktif, supplier, stok menipis, dan ringkasan pembelian/penjualan.
2. **Data barang** — tampilkan contoh produk `TKD0001`, `TKD0002`, dan `TKD0003`; jelaskan bahwa harga beli dan harga jual dicatat terpisah.
3. **Data supplier** — tampilkan supplier `SP0012` (MITRA SANDANG) dan `SP0040` (BARALEK).
4. **Catat pembelian** — pilih supplier dan barang, masukkan kuantitas serta harga beli, lalu simpan. Stok bertambah dan mutasi masuk tercatat.
5. **Transaksi kasir** — cari barang, tambahkan ke keranjang, masukkan kuantitas yang tidak melebihi stok, pilih pembayaran, lalu simpan. Untuk pembayaran tunai, masukkan jumlah uang yang diterima; aplikasi menghitung kembalian.
6. **Struk dan laporan** — buka struk transaksi yang baru dibuat dan laporan penjualan. Untuk melihat riwayat workbook, pilih periode bulanan **April 2026**. Laporan pembelian dan penjualan hanya tersedia untuk owner.
7. **Kartu stok/laporan stok** — perlihatkan perubahan stok akibat pembelian dan penjualan.
8. **Pembatasan peran (opsional)** — keluar, masuk sebagai `kasir`, lalu tunjukkan bahwa menu master barang dan laporan tidak tersedia. `supervisor` dapat mengelola stok/pembelian, tetapi tidak dapat membuka laporan khusus owner.

Nama menu dapat berbeda sedikit sesuai ukuran layar. Data transaksi yang sudah tersimpan bersifat append-only pada versi demo ini dan tidak menyediakan pembatalan/retur.

## 6. Menghentikan aplikasi

Kembali ke terminal server dan tekan **Ctrl+C**. MySQL dapat dihentikan dari Laragon setelah selesai.

## Jika ada kendala

- **Database connection refused / tidak bisa terhubung:** pastikan MySQL Laragon aktif dan `DB_HOST`, `DB_PORT`, serta kredensial di `.env` benar.
- **Database tidak ditemukan:** buat database `toko_delima_mpsi` melalui phpMyAdmin, lalu ulangi `php artisan migrate --seed`.
- **Tidak bisa login:** pastikan `DEMO_USER_PASSWORD` sudah diisi dengan benar, lalu jalankan `php artisan db:seed` kembali. Seeder tidak menghapus data transaksi.
- **Halaman menampilkan error aset Vite:** jalankan `npm install` lalu `npm run build`.
- **Port 8000 sedang digunakan:** jalankan server dengan port lain, misalnya `php artisan serve --host=127.0.0.1 --port=8001`, lalu buka URL dengan port tersebut.

> **Peringatan:** `php artisan migrate:fresh --seed` menghapus semua tabel dan data pada database yang dikonfigurasi, lalu membuat ulang data demo. Gunakan hanya jika database itu khusus demo dan Anda memang ingin mengulang dari awal.
