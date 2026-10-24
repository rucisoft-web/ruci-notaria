# Ruci Notaria

Aplikasi kantor notaris untuk Indonesia: register akta dengan penomoran yang mengikuti UUJN, buku daftar wajib yang tersusun otomatis, pengingat tenggat hukum, keuangan yang memisahkan honorarium dari titipan, serta bantuan AI yang mati sampai notaris menyalakannya.

Ini **Fase 1 (inti kepatuhan)** dari spesifikasi *Spesifikasi Perangkat Lunak Notaris AI*. Integrasi AHU Online, modul PPAT lengkap, dan PMPJ/APU-PPT dijadwalkan pada fase berikutnya.

## Teknologi

- PHP 8.3, Laravel 13, Filament 5 (Livewire). Tidak perlu Node.js di server.
- MySQL 8 atau MariaDB 10.6+.
- Draf akta .docx dengan PHPWord (template berisi variabel `${...}`).
- AI opsional: OpenAI Responses API dengan keluaran JSON terstruktur.
- Cocok untuk shared hosting cPanel: antrean, cache, dan sesi memakai database; cukup satu cron `schedule:run`.

## Fitur Fase 1

| Area | Isi |
|---|---|
| Klien & penghadap | Orang dan badan hukum, NIK/NPWP terenkripsi dengan indeks buta untuk pencarian, relasi keluarga dua arah, persetujuan AI per klien, dokumen identitas dengan masa berlaku |
| Berkas | Nomor otomatis, tahap kerja, PIC dan anggota, daftar periksa dokumen dari jenis layanan, tugas, tanda terima dokumen asli, berkas **sangat rahasia** yang hanya terlihat notaris/PIC/anggota |
| Akta | Penghadap (diri sendiri/kuasa/mewakili badan), saksi akta dan pengenal, jadwal, **pemeriksaan kesiapan** (R-00 s.d. R-17), **penomoran terkunci** per notaris, potret data penghadap saat tanda tangan, renvoi, berita acara pembetulan (Pasal 51), salinan/kutipan/grosse dengan dasar hak (Pasal 54), peminjaman minuta |
| Draf akta | Template .docx yang disetujui notaris, variabel otomatis (hari/tanggal/jam/terbilang, komparisi, saksi), garis pengisi akhir baris, versi draf, pemeriksa draf deterministik (variabel tersisa, nama/NIK, terbilang) |
| Surat di bawah tangan | Legalisasi, waarmerking, dan pengesahan fotokopi dengan nomor `N/Leg/KODE/TAHUN`, cetak redaksi pengesahan |
| Buku daftar | Repertorium, klapper, daftar legalisasi, waarmerking, fotokopi, wasiat, protes; per bulan, cetak A4, **NIHIL** otomatis, paket laporan bulanan MPD dengan surat pengantar |
| Protokol | Penjilidan maksimal 50 minuta per jilid dengan label sampul, buku fisik dan lokasi arsip, permintaan penegak hukum/MKN (Pasal 66), serah terima protokol, halaman kesiapan pemeriksaan MPD |
| Tenggat | Dibuat otomatis saat akta bernomor (AHU PT 60 hari, fidusia 30 hari, PPAT 7 hari kerja, dan lain-lain) dan setiap bulan (MPD tgl 15, daftar wasiat tgl 5, penjilidan); hari kerja melewati hari libur |
| Keuangan | Tagihan honorarium vs titipan pihak ketiga, **batas honorarium Pasal 36** per akta, berkas cuma-cuma (Pasal 37), pembayaran dengan kuitansi, buku dana titipan |
| AI (opsional) | AI-01 ekstraksi identitas dari KTP/dokumen; AI-04 pemeriksa draf. Hanya untuk klien yang setuju, tidak untuk berkas sangat rahasia, data disamarkan sebelum dikirim, batas biaya bulanan, semua panggilan tercatat |
| Keamanan | Peran Notaris / Staf / Keuangan / Arsiparis / Admin TI, MFA, jejak audit dengan **rantai hash**, unduhan berkas lewat izin dan tercatat |

### Peran

| Peran | Hak utama |
|---|---|
| Notaris | Semua, termasuk memberi nomor akta, menyetujui template, menyalakan AI, permintaan penegak hukum |
| Staf / drafter | Klien, berkas, akta (tanpa memberi nomor), draf, legalisasi/waarmerking, tenggat |
| Keuangan | Seperti staf ditambah tagihan, pembayaran, dana titipan |
| Arsiparis | Berkas (baca), protokol, jilid, buku fisik, lokasi arsip |
| Admin TI | Pengguna, hari libur, pengaturan, jejak audit; **tidak dapat melihat berkas klien** |

## Aturan yang dijaga kode

- Nomor akta bulanan mulai dari 1 setiap bulan; nomor urut repertorium berlanjut sepanjang tahun; dikunci per notaris sehingga tidak ada nomor ganda atau celah.
- Tanggal dan jam akta baru tidak boleh lebih awal dari akta bernomor sebelumnya.
- Akta bernomor tidak dapat dihapus dan kolom pokoknya tidak dapat diubah (perbaikan lewat berita acara pembetulan). Entri legalisasi/waarmerking dan pembayaran juga tidak dapat dihapus.
- Kesiapan akta: penghadap 18 tahun atau sudah kawin, identitas berlaku, minimal 2 saksi (4 untuk wasiat bawah tangan), larangan hubungan keluarga Pasal 52 dan Pasal 40, wilayah jabatan, notaris sedang cuti, usia 65 tahun.
- Honorarium: 2,5% / 1,5% / 1% dari nilai ekonomis per akta, Rp5 juta untuk nilai sosiologis.

## Menjalankan di komputer sendiri

```bash
composer install
cp .env.example .env            # APP_ENV=local, APP_DEBUG=true, DB_*, NOTARIA_DEMO=true
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Buka `http://localhost:8000/app`. Data contoh berisi **tiga notaris fiktif** (Kota Bandung, Kota Cimahi, Kabupaten Bandung Barat) dan diberi label DATA CONTOH di setiap layar dan cetakan. Akun: `notaris@`, `notaris2@`, `notaris3@`, `staf@`, `staf2@`, `keuangan@`, `arsip@`, `admin@notaria.demo` dengan sandi `NOTARIA_DEMO_PASSWORD` (bawaan `Demo#2026notaria`), atau tombol **Masuk sebagai tamu** di halaman login.

### Situs demo dan halaman depan

| Variabel | Fungsi |
|---|---|
| `NOTARIA_DEMO=true` | Muat data contoh, tampilkan pita dan tanda air DATA CONTOH |
| `NOTARIA_DEMO_TAMU=true` | Tombol "Masuk sebagai tamu" (akun notaris contoh tanpa pengaturan, profil, dan AI) |
| `NOTARIA_DEMO_RESET_HARIAN=true` | Kembalikan data contoh setiap hari pukul 02.00 |
| `NOTARIA_LANDING=true` | Halaman depan `/` berisi penjelasan fitur, siap dibagikan di LinkedIn (Open Graph 1200×627) |
| `NOTARIA_KONTAK` | Tautan tombol "Hubungi kami" di halaman depan |

Uji otomatis: `php artisan test`. Uji mencakup terbilang, penomoran akta, kunci akta, kesiapan dan hubungan keluarga, tenggat hari kerja, penjilidan, nomor legalisasi, buku daftar dan celah nomor, rantai audit, honorarium, pembayaran, pembuatan draf .docx, pemeriksa draf, penyamaran data AI, serta semua layar dan cetakan untuk setiap peran.

## Pemasangan di cPanel

Lihat [PANDUAN-CPANEL.md](PANDUAN-CPANEL.md).

## Perintah artisan

| Perintah | Fungsi |
|---|---|
| `notaria:create-admin email "Nama" --role=notaris --password=...` | Buat atau perbarui pengguna |
| `notaria:demo` | Muat data contoh tiga kantor notaris fiktif |
| `notaria:demo-reset` | Kosongkan database dan berkas lalu muat ulang data contoh (hanya bila `NOTARIA_DEMO=true`) |
| `notaria:doctor` | Periksa kesiapan server |
| `notaria:tenggat-bulanan [--periode=YYYY-MM]` | Buat tenggat MPD, wasiat, dan penjilidan (otomatis tiap tanggal 1) |
| `notaria:periksa-audit` | Verifikasi rantai hash jejak audit (otomatis tiap Senin) |

## Catatan hukum

Angka tenggat ada di `config/notaria.php` beserta dasar hukumnya; beberapa ditandai *verifikasi* karena bergantung pada peraturan teknis yang dapat berubah. Redaksi template, pengesahan, dan surat pengantar adalah contoh dan harus ditinjau notaris. Aplikasi membantu kepatuhan, tetapi tidak menggantikan pertimbangan dan tanggung jawab notaris.
