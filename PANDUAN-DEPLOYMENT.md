# Panduan Deployment ke Vercel

Panduan ini ditujukan untuk pengelola teknis. Aplikasi menggunakan konfigurasi `vercel.json` dan `Dockerfile` untuk dijalankan dengan Docker dan FrankenPHP. Deployment diarahkan ke region Singapura (`sin1`) dan memerlukan database MySQL yang dapat diakses dari Vercel.

## 1. Hubungkan repository

Import repository GitHub ini sebagai proyek di Vercel. Pastikan root directory proyek mengarah ke direktori yang berisi `vercel.json`.

## 2. Atur environment variables

Tambahkan variabel berikut di **Project Settings > Environment Variables**. Jangan menyimpan nilai rahasia di repository.

| Variabel | Nilai |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Kunci Laravel yang dibuat dengan `php artisan key:generate --show` |
| `APP_URL` | URL deployment aplikasi, misalnya `https://nama-proyek.vercel.app` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | Host database MySQL yang dapat diakses dari Vercel |
| `DB_PORT` | Port database MySQL, biasanya `3306` |
| `DB_DATABASE` | Nama database |
| `DB_USERNAME` | Username database |
| `DB_PASSWORD` | Password database |

Atur variabel untuk environment Vercel yang digunakan (Production, dan Preview jika diperlukan). Gunakan database dan kredensial terpisah untuk Preview bila memungkinkan.

## 3. Siapkan database dan deploy

Sebelum aplikasi digunakan, siapkan schema dengan menjalankan `php artisan migrate --force` dari environment yang memiliki koneksi ke database tersebut. Konfigurasi Docker tidak menjalankan migrasi atau seeder secara otomatis.

Setelah database siap, deploy proyek dari Vercel dan periksa URL deployment. Untuk memperbarui aplikasi, push perubahan ke branch yang terhubung dengan proyek.

Jangan jalankan `php artisan migrate:fresh --seed` pada database produksi. Perintah tersebut menghapus semua tabel dan data, sedangkan seeder membuat akun serta data demo. Jalankan seeder hanya pada database demo yang memang boleh diisi ulang.
