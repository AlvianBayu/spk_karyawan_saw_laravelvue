## Nama Proyek

SPK Kedisiplinan Karyawan (Metode SAW)

Deskripsi Singkat

Aplikasi berbasis web Sistem Pendukung Keputusan (SPK) yang dirancang untuk mengukur, menilai, dan memeringkat tingkat kedisiplinan karyawan secara transparan dan objektif. Sistem ini memproses data kedisiplinan dan absensi mentah (kehadiran, keterlambatan, izin, sakit, dan alpa) menggunakan metode Simple Additive Weighting (SAW), serta dilengkapi dengan fitur kalkulasi sanksi otomatis dan pemantauan per periode.

## Fitur Utama

Otorisasi Multi-Role:

Admin / HRD: Akses penuh untuk mengelola master data, menginput absensi, memproses algoritma SAW, dan mengelola sanksi.

Pimpinan: Akses Read-Only khusus untuk memantau ringkasan statistik dashboard serta mengekspor laporan akhir.

Manajemen Master Data (CRUD):

Data Karyawan (dilengkapi fitur Soft Delete agar riwayat karyawan resign tetap tersimpan).

Data Kriteria & Bobot Penilaian (Benefit & Cost).

Data Periode Penilaian (Bulan & Tahun).

Mesin Kalkulasi SAW & Smart Recalculation:

Penghitungan otomatis normalisasi matriks dan nilai preferensi.

Tombol Pintar (Hitung Ulang): Otomatis mendeteksi jika terjadi revisi data absensi mentah sehingga hasil perangkingan selalu sinkron.

Perhitungan Sanksi Otomatis:

Deteksi otomatis bagi karyawan dengan tingkat pelanggaran tinggi (misal: Alpa berlebih) dan kalkulasi persentase potongan sanksi.

Ekspor Laporan Multiformat:

Unduh hasil perankingan kedisiplinan ke dalam format PDF dan Excel (.xlsx).

Performa Tinggi (SPA Architecture):

Pengalaman pengguna tanpa page reload berbasis Inertia.js dan dioptimalkan untuk skor performa Lighthouse.


## Tech Stack

Backend Framework: Laravel 10 (PHP)

Frontend Framework: Vue.js 3 + Inertia.js

UI & Styling: Tailwind CSS + DaisyUI

Database: MySQL / MariaDB

Pengujian (Testing): PHPUnit (White-Box Testing)

Library Ekspor: Barryvdh DomPDF & Maatwebsite Laravel Excel

## Cara Instalasi

1Instal Dependensi PHP (Backend):
composer install

2 Instal Dependensi JavaScript (Frontend):
npm install

3.Duplikat file .env.example menjadi .env, lalu buat application key:
cp .env.example .env
php artisan key:generate

4 Buka file .env dan sesuaikan kredensial database Anda:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_spk_kedisiplinan
DB_USERNAME=root
DB_PASSWORD=

5 Jalankan migrasi untuk membuat tabel beserta data dummy awal (kriteria, karyawan, dan akun pengguna):
php artisan migrate:fresh --seed

6 Jalankan server frontend dan backend secara bersamaan (di dua jendela terminal terpisah):

npm run dev
php artisan serve


