# Panduan Instalasi Ruci Notaria di cPanel

Panduan ini untuk hosting cPanel biasa (shared hosting atau VPS dengan cPanel). Ada dua jalur: **dengan Terminal/SSH** (lebih mudah) dan **tanpa Terminal** (memakai File Manager dan Cron Jobs).

Contoh memakai akun cPanel `userku`, folder program `/home/userku/ruci-notaria`, dan alamat `https://notaria.domainanda.co.id`. Sesuaikan dengan milik Anda.

> Data kantor notaris bersifat rahasia jabatan. Pakai hosting di Indonesia atau yang memenuhi UU Pelindungan Data Pribadi, aktifkan SSL, dan nyalakan MFA.

## 1. Prasyarat hosting

| Komponen | Ketentuan | Tempat di cPanel |
|---|---|---|
| PHP | **8.3 atau lebih baru** | *MultiPHP Manager* atau *Select PHP Version* |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `intl`, `fileinfo`, `openssl`, `zip`, `xml`, `dom`, `curl`, `bcmath` | *Select PHP Version › Extensions* (terutama **intl**, **zip**, **fileinfo**) |
| Database | **MySQL 8** atau MariaDB 10.6+ | *MySQL Databases* |
| SSL | **Wajib** | *SSL/TLS Status* › Run AutoSSL |
| Cron | **Wajib**, satu baris (tenggat bulanan, antrean, cek audit) | *Cron Jobs* |
| Batas unggah | `upload_max_filesize` dan `post_max_size` minimal 20M | *MultiPHP INI Editor* |

Tidak perlu Node.js, Redis, atau Supervisor.

## 2. Buat database

1. Buka **MySQL Databases**.
2. *Create New Database*: misalnya `notaria` (menjadi `userku_notaria`).
3. *Add New User*: misalnya `notaria` (menjadi `userku_notaria`) dengan kata sandi kuat.
4. *Add User To Database* › centang **ALL PRIVILEGES** › Make Changes.

## 3. Taruh program di server

Letakkan program **di luar `public_html`**, misalnya `/home/userku/ruci-notaria`, supaya `.env`, kode, dan berkas klien di `storage` tidak bisa dibuka dari browser.

### Jalur A: dengan Terminal

```bash
cd ~
git clone <alamat-repo> ruci-notaria     # atau: git clone ruci-notaria.bundle ruci-notaria
cd ruci-notaria
composer install --no-dev --optimize-autoloader
```

Bila `php -v` bukan 8.3, panggil PHP 8.3 langsung, misalnya `/opt/cpanel/ea-php83/root/usr/bin/php` (EasyApache) atau `/opt/alt/php83/usr/bin/php` (CloudLinux).

### Jalur B: tanpa Terminal

1. Pakai paket **`ruci-notaria-cpanel.zip`** (sudah berisi folder `vendor`, tidak perlu Composer).
2. *File Manager* › `/home/userku` › **Upload** zip tersebut.
3. Klik kanan zip › **Extract** ke `/home/userku`. Hasilnya folder `/home/userku/ruci-notaria`.
4. Hapus file zip setelah diekstrak.

## 4. Arahkan domain ke folder `public`

### Pilihan 1 (disarankan): subdomain atau addon domain

1. *Domains* › **Create A New Domain**, misalnya `notaria.domainanda.co.id`.
2. Matikan *Share document root*, isi **Document Root**: `ruci-notaria/public`.
3. Simpan, lalu aktifkan SSL.

### Pilihan 2: domain utama yang terkunci di `public_html`

1. Salin **seluruh isi** `ruci-notaria/public` ke `public_html`, termasuk `.htaccess` (File Manager › *Settings* › *Show Hidden Files*).
2. Edit `public_html/index.php`, ganti tiga baris yang berisi `__DIR__.'/../` menjadi:

```php
if (file_exists($maintenance = __DIR__.'/../ruci-notaria/storage/framework/maintenance.php')) {
require __DIR__.'/../ruci-notaria/vendor/autoload.php';
$app = require_once __DIR__.'/../ruci-notaria/bootstrap/app.php';
```

3. Setiap memperbarui program, salin ulang isi `ruci-notaria/public` (kecuali `index.php`).

## 5. Isi file `.env`

Di folder `ruci-notaria`, salin `.env.example` menjadi `.env`, lalu *Edit*:

```dotenv
APP_NAME="Ruci Notaria"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://notaria.domainanda.co.id
APP_TIMEZONE=Asia/Jakarta

DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=userku_notaria
DB_USERNAME=userku_notaria
DB_PASSWORD="kata-sandi-database"

SESSION_SECURE_COOKIE=true

# Sangat disarankan true setelah semua pengguna memasang aplikasi autentikator.
NOTARIA_MFA_REQUIRED=false

# Akun notaris pertama (dibuat saat migrate --seed). Kosongkan lagi setelah instalasi.
NOTARIA_ADMIN_EMAIL=notaris@domainanda.co.id
NOTARIA_ADMIN_PASSWORD="SandiSementara#2026"

NOTARIA_DEMO=false

# Opsional. Walau diisi, AI tetap mati sampai notaris menyalakannya di Pengaturan Kantor.
OPENAI_API_KEY=
```

`APP_KEY` dibiarkan kosong; diisi oleh `key:generate`. **Simpan salinan `APP_KEY` di tempat aman**: NIK, NPWP, dan potret data penghadap dienkripsi dengan kunci ini. Bila hilang, data tersebut tidak dapat dibaca lagi.

Kata sandi yang mengandung `#` atau spasi harus diberi tanda kutip.

## 6. Kunci aplikasi, tabel, dan akun notaris

### Dengan Terminal

```bash
cd ~/ruci-notaria
php artisan key:generate --force
php artisan migrate --seed --force
php artisan optimize
php artisan notaria:doctor
```

### Tanpa Terminal (Cron Jobs sekali jalan)

Tambahkan satu per satu di **Cron Jobs** dengan jadwal *Once Per Minute*. Tunggu 1 sampai 2 menit, periksa hasilnya, lalu **hapus** cron itu sebelum menambah berikutnya.

1. `cd /home/userku/ruci-notaria && php artisan key:generate --force > storage/logs/setup-1.txt 2>&1`
2. `cd /home/userku/ruci-notaria && php artisan migrate --seed --force > storage/logs/setup-2.txt 2>&1`
3. `cd /home/userku/ruci-notaria && php artisan optimize > storage/logs/setup-3.txt 2>&1`
4. `cd /home/userku/ruci-notaria && php artisan notaria:doctor > storage/logs/setup-4.txt 2>&1`

Setelah berhasil, kosongkan `NOTARIA_ADMIN_PASSWORD` di `.env` dan jalankan `php artisan optimize` lagi.

## 7. Cron permanen (wajib)

Tambahkan **satu** cron dengan jadwal *Once Per Minute*:

```
cd /home/userku/ruci-notaria && php artisan schedule:run >> /dev/null 2>&1
```

Cron ini membuat tenggat laporan MPD, daftar wasiat, dan penjilidan setiap tanggal 1, menjalankan antrean, dan memeriksa rantai jejak audit setiap Senin. Gunakan path PHP 8.3 bila `php` di cron bukan 8.3.

## 8. Mulai memakai

Masuk di `https://notaria.domainanda.co.id/app` dengan akun notaris, lalu:

1. **Pengaturan › Pengaturan Kantor**: nama kantor, kota, zona waktu jam akta (WIB/WITA/WIT).
2. **Pengaturan › Jabatan Notaris**: nama lengkap dengan gelar sebagaimana ditulis di awal akta, SK, tempat kedudukan, provinsi (wilayah jabatan), dan **kode** untuk nomor legalisasi (misal `RK` menjadi `1/Leg/RK/2026`). Bila ada notaris pengganti, tambahkan juga.
3. **Pengaturan › Pengguna**: buat akun staf, keuangan, arsiparis, dan admin TI.
4. **Pengaturan › Hari Libur**: isi libur nasional dan cuti bersama tahun berjalan (untuk tenggat hari kerja).
5. **Pengaturan › Jenis Layanan**: periksa katalog layanan, daftar periksa dokumen, dan tenggatnya.
6. **Pengaturan › Template Akta**: klik *Buat template contoh* atau unggah template .docx kantor, lalu **Setujui** (hanya notaris).
7. Tandai staf yang biasa menjadi saksi: di **Klien & Penghadap**, buat data staf tersebut dan centang *Saksi kantor*.
8. Bila memakai nomor akta lanjutan di tengah tahun, catat dulu akta lama sebelum mulai memakai aplikasi, atau hubungi pengembang untuk impor.

Alur harian: **Berkas** › tambah pihak dan dokumen › **Akta** › penghadap dan saksi › *Buat draf* › *Periksa kesiapan* › *Jadwalkan* › setelah ditandatangani, notaris klik **Catat penandatanganan & beri nomor**. Nomor, buku daftar, dan tenggat tersusun otomatis.

Akhir bulan: **Buku Daftar** › pilih bulan › *Paket laporan bulanan MPD* › cetak/simpan PDF. **Jilid Minuta** › *Susun jilid bulan* › cetak label.

### Menyalakan AI (opsional)

1. Isi `OPENAI_API_KEY` di `.env`, jalankan `php artisan optimize`.
2. Notaris membuka **Pengaturan Kantor** › nyalakan *Izinkan fitur AI*, pilih fitur, dan batas biaya per bulan.
3. Centang *Persetujuan AI* pada data klien yang setuju datanya diproses. Berkas *sangat rahasia* tidak pernah dikirim ke AI.
4. Semua panggilan tercatat di **Pengaturan › Log AI**.

## 9. Situs demo dan halaman promosi

Situs demo dipasang **terpisah** dari kantor sungguhan, misalnya di subdomain `notaria.rucisoft.id` dengan database sendiri. Jangan pernah menyalakan pengaturan di bawah ini pada instalasi yang berisi data klien.

Tambahkan ke `.env` situs demo. `APP_URL` wajib persis sama dengan alamat situs, karena gambar pratinjau LinkedIn memakai alamat lengkap:

```
APP_URL=https://notaria.rucisoft.id
NOTARIA_DEMO=true
NOTARIA_DEMO_TAMU=true
NOTARIA_DEMO_RESET_HARIAN=true
NOTARIA_LANDING=true
NOTARIA_KONTAK=https://wa.me/62812xxxxxxx
```

Lalu jalankan `php artisan migrate --force`, `php artisan notaria:demo`, dan `php artisan optimize`.

- **Halaman depan** (`https://notaria.rucisoft.id/`) menjelaskan fitur untuk notaris dan siap dibagikan di LinkedIn: judul, ringkasan, dan gambar pratinjau 1200×627 sudah terpasang. Setelah situs online, tempel alamatnya di [LinkedIn Post Inspector](https://www.linkedin.com/post-inspector/) agar pratinjau dimuat ulang.
- **Masuk sebagai tamu**: tombol di halaman depan dan halaman login. Tamu masuk tanpa sandi dengan peran **Tamu (hanya lihat)**: semua berkas, akta, buku daftar, keuangan, dan protokol dapat dilihat, tetapi tombol tambah, ubah, hapus, dan beri nomor disembunyikan, setiap penyimpanan ditolak, dan profil serta fitur AI tidak tersedia.
- **Mengapa Ruci Notaria** (`/mengapa-ruci-notaria`) dan **Panduan penggunaan** (`/panduan`): terbuka tanpa login, ditautkan dari halaman login dan halaman depan, cocok untuk dibagikan di LinkedIn.
- **Label DATA CONTOH**: pita kuning di setiap layar dan tanda air di setiap cetakan.
- **Reset harian**: cron `schedule:run` (langkah 7) menjalankan `notaria:demo-reset` setiap pukul 02.00, yaitu mengosongkan database dan berkas lalu memuat ulang data contoh. Perintah ini menolak berjalan bila `NOTARIA_DEMO` bukan `true`.

Data contoh berisi tiga notaris fiktif di Jawa Barat. Tanggal dihitung mundur dari hari reset sehingga dasbor selalu terlihat baru.

| Notaris | Kode | Isi |
|---|---|---|
| Dr. Rina Kartika, S.H., M.Kn. (Notaris & PPAT, Kota Bandung) | RK | sewa ruko, kuasa, fidusia bank, PPJB dengan tagihan dibayar sebagian, pendirian PT dijadwalkan besok, wasiat sangat rahasia |
| Bambang Hartono, S.H., M.Kn. (Notaris, Kota Cimahi) | BH | pendirian CV dan yayasan, perjanjian kawin, akta wasiat, pengakuan utang dijadwalkan hari ini, tagihan lunas |
| Ayu Pratiwi Handayani, S.H., M.Kn. (Notaris & PPAT, Kab. Bandung Barat) | AP | PPJB tanah, keterangan waris, kuasa, perjanjian kerja sama yang tertahan karena saksi bersaudara dengan penghadap (temuan R-04) |

| Peran | Email |
|---|---|
| Notaris | notaris@notaria.demo (Rina), notaris2@notaria.demo (Bambang), notaris3@notaria.demo (Ayu) |
| Staf | staf@notaria.demo, staf2@notaria.demo |
| Keuangan | keuangan@notaria.demo |
| Arsiparis | arsip@notaria.demo |
| Admin TI | admin@notaria.demo |
| Tamu | tombol "Masuk sebagai tamu" (tanpa sandi) |

Sandi semua akun kecuali tamu: `NOTARIA_DEMO_PASSWORD` (bawaan `Demo#2026notaria`). Ganti sandi ini di situs demo publik agar pengunjung hanya memakai akun tamu.

## 10. Memperbarui program

Dengan Terminal:

```bash
cd ~/ruci-notaria
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan up
```

Tanpa Terminal: upload zip versi baru, ekstrak menimpa folder `ruci-notaria` (zip tidak berisi `.env` dan tidak menghapus `storage`), lalu jalankan `php artisan migrate --force` dan `php artisan optimize` lewat cron sekali jalan.

## 11. Cadangan (backup)

Cadangkan **setiap hari**: database (cPanel *Backup* atau *phpMyAdmin › Export*) dan folder `storage/app/private` (dokumen klien, draf, template, pindaian minuta). Simpan juga `.env` (berisi `APP_KEY`) terpisah di tempat aman. Uji pemulihan cadangan secara berkala.

## 12. Masalah umum

| Gejala | Solusi |
|---|---|
| Error 500 tanpa keterangan | Lihat `storage/logs/laravel-*.log`. Sementara boleh `APP_DEBUG=true`, lalu kembalikan ke `false`. |
| `Your PHP version … does not satisfy` | Ubah PHP domain dan cron ke 8.3. |
| `Specified key was too long` | Pakai MySQL 8 / MariaDB 10.6+ dengan utf8mb4. |
| Buat draf gagal / berkas .docx rusak | Aktifkan ekstensi **zip** dan **xml**. |
| Tenggat bulanan tidak muncul | Cron `schedule:run` belum dipasang. Sementara jalankan `php artisan notaria:tenggat-bulanan`. |
| `The MAC is invalid` / data NIK tidak terbaca | `APP_KEY` berubah. Kembalikan `APP_KEY` lama dari cadangan `.env`. |
| `notaria:periksa-audit` gagal | Ada baris jejak audit yang diubah atau dihapus langsung di database. Laporkan kepada notaris dan periksa akses database. |
| Halaman tampil tanpa gaya | Document root belum ke `ruci-notaria/public`. |
| Semua halaman selain beranda 404 | `.htaccess` dari `public` belum tersalin. |
| `419 Page Expired` saat login | `APP_URL` harus sama dengan alamat yang dibuka; pastikan SSL aktif. |
| Unggah gagal | Naikkan `upload_max_filesize`/`post_max_size`; aktifkan **fileinfo**. |
| Pengguna kehilangan ponsel MFA | Notaris/admin TI › Pengguna › *Reset MFA*. |
| Perubahan `.env` tidak berlaku | `php artisan optimize:clear` lalu `php artisan optimize`. |
