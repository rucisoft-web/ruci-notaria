@php
    $tamu = \App\Http\Controllers\TamuController::tersedia();
    $kontak = config('notaria.kontak');
    $judul = 'Ruci Notaria: aplikasi kantor notaris yang patuh UUJN';
    $ringkas = 'Register akta dengan penomoran terkunci, buku daftar dan laporan MPD otomatis, pengingat tenggat AHU, keuangan honorarium vs titipan, dan AI yang aman. Coba langsung sebagai tamu dengan data contoh 3 notaris.';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }}</title>
    <meta name="description" content="{{ $ringkas }}">
    <link rel="canonical" href="{{ url('/') }}">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    {{-- Pratinjau tautan LinkedIn, WhatsApp, Facebook, dan X --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="Ruci Notaria">
    <meta property="og:title" content="{{ $judul }}">
    <meta property="og:description" content="{{ $ringkas }}">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('img/og-ruci-notaria.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="627">
    <meta property="og:image:alt" content="Ruci Notaria: register akta, buku daftar, tenggat, dan laporan MPD dalam satu aplikasi">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $judul }}">
    <meta name="twitter:description" content="{{ $ringkas }}">
    <meta name="twitter:image" content="{{ asset('img/og-ruci-notaria.png') }}">

    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ @filemtime(public_path('css/landing.css')) }}">
</head>
<body>
<header class="kepala">
    <div class="wadah kepala-isi">
        <a href="{{ url('/') }}" class="merek">
            <img src="{{ asset('img/logo/ruci-notaria.svg') }}" alt="Ruci Notaria" width="188" height="40">
        </a>
        <nav class="navigasi" aria-label="Utama">
            <a href="#fitur">Fitur</a>
            <a href="#demo">Data contoh</a>
            <a href="#keamanan">Keamanan &amp; AI</a>
            <a href="{{ route('panduan.mengapa') }}">Mengapa Ruci Notaria</a>
            <a href="{{ route('panduan.penggunaan') }}">Panduan</a>
            <a href="https://rucisoft.id">Rucisoft.id</a>
        </nav>
        <a href="{{ url('/app/login') }}" class="tombol tombol-garis kecil">Masuk</a>
    </div>
</header>

<main>
    {{-- Pembuka --}}
    <section class="pembuka">
        <div class="wadah pembuka-isi">
            <div class="pembuka-teks">
                <p class="pita">Untuk notaris dan PPAT di Indonesia</p>
                <h1>Kantor notaris yang rapi, patuh UUJN, dan siap diperiksa MPD.</h1>
                <p class="lead">Ruci Notaria mencatat setiap berkas dari klien datang sampai minuta dijilid. Nomor akta tidak bisa ganda atau melompat, buku daftar tersusun sendiri setiap bulan, dan tenggat AHU tidak lagi bergantung pada ingatan.</p>
                <div class="aksi">
                    @if ($tamu)
                        <form method="POST" action="{{ route('tamu.masuk') }}">
                            @csrf
                            <button type="submit" class="tombol tombol-utama">Coba sebagai tamu</button>
                        </form>
                    @endif
                    <a href="{{ route('panduan.mengapa') }}" class="tombol tombol-garis">Mengapa Ruci Notaria?</a>
                </div>
                @if ($tamu)
                    <p class="catatan-kecil">Tanpa mendaftar. Anda masuk ke kantor fiktif berisi <b>data contoh 3 notaris</b>, hanya untuk dilihat. <a href="{{ route('panduan.penggunaan') }}">Baca panduan penggunaan</a>.</p>
                @endif
            </div>
            <figure class="bingkai pembuka-gambar">
                <span class="label-contoh">DATA CONTOH</span>
                <img src="{{ asset('img/landing/dasbor.png') }}" alt="Dasbor Ruci Notaria berisi tenggat 7 hari ke depan, akta terjadwal, dan ringkasan bulan ini" width="1280" height="800">
            </figure>
        </div>
    </section>

    {{-- Masalah --}}
    @include('partials.rucisoft-alur')

    {{-- Fitur --}}
    <section class="bagian bagian-lembut" id="fitur">
        <div class="wadah">
            <p class="pita">Fitur</p>
            <h2>Satu aplikasi dari berkas masuk sampai protokol tersimpan</h2>
            <div class="kisi kisi-3">
                <article class="fitur">
                    <h3>Klien &amp; penghadap</h3>
                    <p>Orang dan badan hukum, NIK dan NPWP terenkripsi tetapi tetap dapat dicari, relasi keluarga dua arah, dokumen identitas dengan masa berlaku.</p>
                </article>
                <article class="fitur">
                    <h3>Berkas &amp; daftar periksa</h3>
                    <p>Nomor berkas otomatis, tahap kerja, PIC, daftar dokumen per jenis layanan, tanda terima dokumen asli, dan berkas sangat rahasia.</p>
                </article>
                <article class="fitur">
                    <h3>Pemeriksaan kesiapan akta</h3>
                    <p>Sebelum tanda tangan: umur dan status kawin penghadap, identitas berlaku, jumlah saksi, larangan hubungan keluarga Pasal 40 dan 52, wilayah jabatan, cuti, usia 65 tahun.</p>
                </article>
                <article class="fitur">
                    <h3>Penomoran akta terkunci</h3>
                    <p>Nomor bulanan dan nomor urut repertorium diberikan saat penandatanganan, dikunci per notaris, tidak bisa ganda, tidak bisa mundur, dan akta bernomor tidak bisa dihapus.</p>
                </article>
                <article class="fitur">
                    <h3>Draf akta .docx</h3>
                    <p>Template Word yang disetujui notaris, variabel otomatis (hari, tanggal, terbilang, komparisi, saksi), garis pengisi, versi draf, dan pemeriksa draf.</p>
                </article>
                <article class="fitur">
                    <h3>Legalisasi &amp; waarmerking</h3>
                    <p>Nomor <code>N/Leg/KODE/TAHUN</code> otomatis untuk legalisasi, waarmerking, dan pengesahan fotokopi, lengkap dengan cetak redaksi pengesahan.</p>
                </article>
                <article class="fitur">
                    <h3>Buku daftar &amp; laporan MPD</h3>
                    <p>Repertorium, klapper, daftar legalisasi, waarmerking, wasiat, dan protes per bulan, siap cetak A4, NIHIL otomatis, beserta surat pengantar MPD.</p>
                </article>
                <article class="fitur">
                    <h3>Tenggat hukum</h3>
                    <p>Dibuat sendiri saat akta bernomor (AHU PT, fidusia, PPAT 7 hari kerja) dan setiap bulan (MPD, wasiat, penjilidan). Hari kerja melewati hari libur.</p>
                </article>
                <article class="fitur">
                    <h3>Keuangan Pasal 36</h3>
                    <p>Tagihan memisahkan honorarium dari titipan pihak ketiga, batas honorarium per akta, berkas cuma-cuma Pasal 37, kuitansi, dan buku dana titipan.</p>
                </article>
                <article class="fitur">
                    <h3>Protokol notaris</h3>
                    <p>Penjilidan maksimal 50 minuta per jilid dengan label sampul, lokasi arsip, permintaan penegak hukum atau MKN (Pasal 66), dan serah terima protokol.</p>
                </article>
                <article class="fitur">
                    <h3>Salinan &amp; pembetulan</h3>
                    <p>Salinan, kutipan, dan grosse dengan dasar hak (Pasal 54), renvoi, berita acara pembetulan (Pasal 51), dan peminjaman minuta.</p>
                </article>
                <article class="fitur">
                    <h3>Siap pemeriksaan MPD</h3>
                    <p>Satu halaman yang memperlihatkan laporan terlambat, minuta belum dipindai atau belum dijilid, sidik jari belum lengkap, dan minuta dipinjam melewati jatuh tempo sebelum Majelis Pengawas datang.</p>
                </article>
            </div>
        </div>
    </section>

    {{-- Sorotan dengan tangkapan layar --}}
    <section class="bagian">
        <div class="wadah sorotan">
            <div>
                <p class="pita">Sebelum tanda tangan</p>
                <h2>Aplikasi menolak memberi nomor bila ada yang melanggar UUJN</h2>
                <p>Dalam data contoh, saksi kantor Nadia Rahma ternyata bersaudara dengan penghadap Fikri Rahman. Pemeriksaan kesiapan menandainya sebagai temuan kritis berdasarkan Pasal 40 UUJN, sehingga nomor tidak dapat diberikan sampai saksi diganti atau notaris menuliskan alasannya.</p>
                <ul class="centang">
                    <li>Aturan kesiapan berkode R-00 sampai R-17, masing-masing dengan dasar pasalnya</li>
                    <li>Hubungan keluarga dibaca dari relasi klien, termasuk semenda</li>
                    <li>Data penghadap dipotret saat tanda tangan</li>
                </ul>
            </div>
            <figure class="bingkai">
                <span class="label-contoh">DATA CONTOH</span>
                <img src="{{ asset('img/landing/kesiapan.png') }}" alt="Pemeriksaan kesiapan akta menampilkan temuan kritis saksi bersaudara dengan penghadap" width="1280" height="800" loading="lazy">
            </figure>
        </div>
        <div class="wadah sorotan terbalik">
            <div>
                <p class="pita">Setiap bulan</p>
                <h2>Buku daftar dan laporan MPD tersusun dari akta yang sudah bernomor</h2>
                <p>Pilih notaris dan bulan, lalu cetak. Buku yang tidak berisi akan bertuliskan NIHIL. Surat pengantar ke Majelis Pengawas Daerah ikut dibuat, dan tenggat tanggal 15 masuk ke dasbor.</p>
                <ul class="centang">
                    <li>Repertorium, klapper, legalisasi, waarmerking, fotokopi, wasiat, protes</li>
                    <li>Cetak A4 dari peramban, tanpa aplikasi tambahan</li>
                    <li>Celah nomor langsung terlihat</li>
                </ul>
            </div>
            <figure class="bingkai">
                <span class="label-contoh">DATA CONTOH</span>
                <img src="{{ asset('img/landing/laporan-mpd.png') }}" alt="Paket laporan bulanan MPD berisi repertorium dan surat pengantar" width="1280" height="800" loading="lazy">
            </figure>
        </div>
        <div class="wadah sorotan">
            <div>
                <p class="pita">Keuangan</p>
                <h2>Honorarium dan titipan tidak lagi bercampur</h2>
                <p>Setiap tagihan memisahkan honorarium notaris dari titipan pihak ketiga seperti pajak, PNBP, atau biaya pengecekan sertipikat. Batas honorarium Pasal 36 dihitung per akta dari nilai ekonomisnya, dan pembayaran langsung menghasilkan kuitansi.</p>
                <ul class="centang">
                    <li>2,5% / 1,5% / 1% dari nilai ekonomis, Rp5 juta untuk nilai sosiologis</li>
                    <li>Buku dana titipan per berkas</li>
                    <li>Kuitansi dengan jumlah terbilang</li>
                </ul>
            </div>
            <figure class="bingkai">
                <span class="label-contoh">DATA CONTOH</span>
                <img src="{{ asset('img/landing/tagihan.png') }}" alt="Tagihan yang memisahkan honorarium akta PPJB dari titipan pengecekan sertipikat, dengan batas honorarium berkas" width="1280" height="800" loading="lazy">
            </figure>
        </div>
    </section>

    {{-- Data contoh --}}
    <section class="bagian bagian-gelap" id="demo">
        <div class="wadah">
            <p class="pita pita-terang">Coba sendiri</p>
            <h2>Kantor fiktif dengan data contoh tiga notaris</h2>
            <p class="lead">Semua nama, NIK, alamat, dan nomor di bawah ini rekaan. Setiap layar dan cetakan di situs demo diberi label <b>DATA CONTOH</b>.</p>
            <div class="kisi kisi-3">
                <article class="notaris">
                    <span class="kode">RK</span>
                    <h3>Dr. Rina Kartika, S.H., M.Kn.</h3>
                    <p class="tempat">Notaris &amp; PPAT, Kota Bandung</p>
                    <p>Sewa ruko, kuasa, jaminan fidusia untuk bank, PPJB rumah dengan tagihan dibayar sebagian, pendirian PT yang dijadwalkan besok, dan berkas wasiat sangat rahasia.</p>
                </article>
                <article class="notaris">
                    <span class="kode">BH</span>
                    <h3>Bambang Hartono, S.H., M.Kn.</h3>
                    <p class="tempat">Notaris, Kota Cimahi</p>
                    <p>Pendirian CV dan yayasan, perjanjian perkawinan, akta wasiat yang masuk daftar wasiat, pengakuan utang yang ditandatangani hari ini, dan tagihan lunas.</p>
                </article>
                <article class="notaris">
                    <span class="kode">AP</span>
                    <h3>Ayu Pratiwi Handayani, S.H., M.Kn.</h3>
                    <p class="tempat">Notaris &amp; PPAT, Kabupaten Bandung Barat</p>
                    <p>PPJB tanah kebun, keterangan hak waris, kuasa mengurus sertipikat, dan perjanjian kerja sama yang tertahan karena saksi bersaudara dengan penghadap.</p>
                </article>
            </div>
            @if ($tamu)
                <div class="aksi tengah">
                    <form method="POST" action="{{ route('tamu.masuk') }}">
                        @csrf
                        <button type="submit" class="tombol tombol-emas">Masuk sebagai tamu</button>
                    </form>
                </div>
                <p class="catatan-kecil tengah terang">Akun tamu dipakai bersama pengunjung lain dan hanya dapat melihat: tombol tambah, ubah, hapus, dan beri nomor disembunyikan, dan data tidak dapat diubah. Jangan memasukkan data pribadi sungguhan.@if (config('notaria.demo.reset_harian')) Data dikembalikan ke keadaan awal setiap hari.@endif</p>
            @endif
        </div>
    </section>

    {{-- Keamanan & AI --}}
    <section class="bagian" id="keamanan">
        <div class="wadah">
            <p class="pita">Keamanan &amp; AI</p>
            <h2>Rahasia jabatan tetap terjaga</h2>
            <div class="kisi kisi-2">
                <article class="kartu">
                    <h3>Akses dan jejak</h3>
                    <ul class="centang">
                        <li>Peran Notaris, Staf, Keuangan, Arsiparis, dan Admin TI. Admin TI tidak dapat membuka berkas klien.</li>
                        <li>Berkas sangat rahasia hanya terlihat oleh notaris, PIC, dan anggota berkas.</li>
                        <li>Verifikasi dua langkah (MFA), NIK dan NPWP terenkripsi.</li>
                        <li>Jejak audit berantai hash sehingga perubahan langsung di database ketahuan.</li>
                        <li>Unduhan dokumen lewat izin dan tercatat.</li>
                    </ul>
                </article>
                <article class="kartu">
                    <h3>AI yang mati sampai notaris menyalakannya</h3>
                    <ul class="centang">
                        <li>Membaca identitas dari foto KTP dan dokumen, lalu mengisi data klien.</li>
                        <li>Memeriksa draf akta: variabel tersisa, nama dan NIK yang tidak cocok, terbilang.</li>
                        <li>Hanya untuk klien yang sudah setuju, tidak pernah untuk berkas sangat rahasia.</li>
                        <li>Data disamarkan sebelum dikirim, ada batas biaya bulanan, dan setiap panggilan tercatat.</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- Pemasangan --}}
    <section class="bagian bagian-lembut">
        <div class="wadah">
            <p class="pita">Pemasangan</p>
            <h2>Berjalan di hosting cPanel biasa</h2>
            <ol class="langkah">
                <li><b>Unggah</b> paket aplikasi ke hosting dengan PHP 8.3 dan MySQL 8 atau MariaDB 10.6.</li>
                <li><b>Isi</b> data jabatan notaris: nama, SK, tempat kedudukan, dan kode untuk nomor legalisasi.</li>
                <li><b>Pasang</b> satu cron <code>schedule:run</code>. Tenggat bulanan dan pemeriksaan audit berjalan sendiri.</li>
            </ol>
            <p class="catatan-kecil">Tidak memerlukan Node.js, Redis, atau server khusus. Antrean, cache, dan sesi memakai database.</p>
        </div>
    </section>

    {{-- Tanya jawab --}}
    <section class="bagian" id="tanya">
        <div class="wadah sempit">
            <h2>Tanya jawab</h2>
            <details>
                <summary>Apakah data contoh di situs demo milik klien sungguhan?</summary>
                <p>Tidak. Ketiga notaris, semua klien, NIK, alamat, dan nomor surat adalah rekaan. Setiap layar dan cetakan diberi label DATA CONTOH.</p>
            </details>
            <details>
                <summary>Apakah satu aplikasi bisa dipakai beberapa notaris?</summary>
                <p>Bisa. Setiap jabatan notaris punya penomoran, buku daftar, dan laporan sendiri. Data contoh memperlihatkan tiga notaris dalam satu kantor bersama.</p>
            </details>
            <details>
                <summary>Apakah aplikasi ini sudah terhubung ke AHU Online?</summary>
                <p>Belum. Fase ini mengingatkan tenggat AHU dan menyiapkan datanya. Integrasi AHU Online, modul PPAT lengkap, dan PMPJ/APU-PPT dijadwalkan pada fase berikutnya.</p>
            </details>
            <details>
                <summary>Apakah AI melihat seluruh data kantor?</summary>
                <p>Tidak. AI mati sampai notaris menyalakannya, hanya memproses klien yang memberi persetujuan, tidak pernah menyentuh berkas sangat rahasia, dan data disamarkan sebelum dikirim.</p>
            </details>
            <details>
                <summary>Apakah aplikasi menggantikan pertimbangan notaris?</summary>
                <p>Tidak. Aplikasi membantu kepatuhan dan mengurangi kesalahan administrasi. Redaksi akta, template, dan keputusan tetap menjadi tanggung jawab notaris.</p>
            </details>
        </div>
    </section>

    {{-- Penutup --}}
    <section class="penutup">
        <div class="wadah tengah">
            <h2>Lihat sendiri dalam lima menit</h2>
            <p class="lead">Buka dasbor, beri nomor akta, lalu cetak repertorium bulan lalu.</p>
            <div class="aksi tengah">
                @if ($tamu)
                    <form method="POST" action="{{ route('tamu.masuk') }}">
                        @csrf
                        <button type="submit" class="tombol tombol-emas">Coba sebagai tamu</button>
                    </form>
                @endif
                @if ($kontak)
                    <a href="{{ $kontak }}" class="tombol tombol-garis-terang" rel="noopener">Hubungi kami</a>
                @endif
                <a href="{{ url('/app/login') }}" class="tombol tombol-garis-terang">Masuk</a>
            </div>
        </div>
    </section>
</main>

<footer class="kaki">
    <div class="wadah">
        <p><b>Ruci Notaria</b> &middot; Aplikasi kantor notaris untuk Indonesia &middot; produk <a href="https://rucisoft.id" style="color:inherit;text-decoration:underline">Rucisoft</a> &middot; <a href="https://rucisoft.id" style="color:inherit">rucisoft.id</a></p>
        <p>Aplikasi membantu kepatuhan, tetapi tidak menggantikan pertimbangan dan tanggung jawab notaris. Data pada situs demo adalah data contoh fiktif.</p>
    </div>
</footer>
</body>
</html>
