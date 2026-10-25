@php
    $daftarIsi = [
        'masuk' => 'Masuk dan peran pengguna',
        'dasbor' => 'Dasbor',
        'klien' => 'Klien dan penghadap',
        'berkas' => 'Berkas pekerjaan',
        'akta' => 'Membuat akta',
        'nomor' => 'Memberi nomor akta',
        'salinan' => 'Salinan, pembetulan, dan peminjaman minuta',
        'legalisasi' => 'Legalisasi dan waarmerking',
        'buku' => 'Buku daftar dan laporan MPD',
        'tenggat' => 'Tenggat',
        'keuangan' => 'Tagihan dan dana titipan',
        'protokol' => 'Protokol dan pemeriksaan',
        'pengaturan' => 'Pengaturan dan AI',
        'tips' => 'Tips dan pertanyaan umum',
    ];
@endphp

<p class="rp-lead">Panduan ini mengikuti urutan kerja sehari-hari di kantor notaris: dari klien datang, berkas dibuka, akta disusun dan ditandatangani, sampai buku daftar dilaporkan ke MPD dan minuta dijilid. Setiap langkah disertai tangkapan layar dari situs demo.</p>

<nav class="rp-daftar-isi" aria-label="Daftar isi">
    <strong>Daftar isi</strong>
    <ol>
        @foreach ($daftarIsi as $id => $judul)
            <li><a href="#{{ $id }}">{{ $judul }}</a></li>
        @endforeach
    </ol>
</nav>

<section id="masuk" class="rp-bagian">
    <h2><span class="rp-nomor">1</span> Masuk dan peran pengguna</h2>
    <p>Buka halaman masuk, isi email dan kata sandi, lalu masukkan kode MFA bila notaris mewajibkannya. Di situs demo tersedia tombol <b>Masuk sebagai tamu</b> untuk melihat seluruh aplikasi tanpa mendaftar.</p>
    @include('panduan.gambar', ['src' => 'login', 'alt' => 'Halaman masuk dengan tombol masuk sebagai tamu dan tautan panduan', 'ket' => 'Halaman masuk. Tautan ke halaman Mengapa Ruci Notaria dan panduan ini ada di bawah formulir.'])
    <div class="rp-tabel-gulir" role="region" aria-label="Hak akses per peran" tabindex="0">
        <table class="rp-tabel">
            <thead><tr><th>Peran</th><th>Yang dapat dilakukan</th></tr></thead>
            <tbody>
                <tr><td>Notaris</td><td>Semua berkas kantornya, memberi nomor akta, menyetujui template, mengesampingkan temuan dengan alasan, dan menyalakan AI.</td></tr>
                <tr><td>Staf</td><td>Klien, berkas, akta, draf, legalisasi, dan tugas pada berkas yang menjadi tanggung jawabnya.</td></tr>
                <tr><td>Keuangan</td><td>Tagihan, pembayaran, kuitansi, dan dana titipan.</td></tr>
                <tr><td>Arsiparis</td><td>Lokasi arsip, buku daftar fisik, penjilidan minuta, peminjaman, dan serah terima protokol.</td></tr>
                <tr><td>Admin TI</td><td>Pengguna, pengaturan kantor, hari libur, dan jejak audit.</td></tr>
                <tr><td>Tamu</td><td>Hanya melihat. Tombol tambah, ubah, hapus, dan beri nomor disembunyikan, dan setiap penyimpanan ditolak.</td></tr>
            </tbody>
        </table>
    </div>
</section>

<section id="dasbor" class="rp-bagian">
    <h2><span class="rp-nomor">2</span> Dasbor</h2>
    <p>Dasbor adalah halaman pertama setelah masuk. Isinya tenggat tujuh hari ke depan (yang terlambat berwarna merah), jadwal tanda tangan hari ini dan besok, berkas yang menunggu tindakan, serta ringkasan akta dan tagihan bulan ini.</p>
    @include('panduan.gambar', ['src' => 'dasbor', 'alt' => 'Dasbor berisi tenggat, jadwal tanda tangan, dan ringkasan bulan ini', 'ket' => 'Mulailah hari dari dasbor: tenggat merah dikerjakan lebih dulu.'])
</section>

<section id="klien" class="rp-bagian">
    <h2><span class="rp-nomor">3</span> Klien dan penghadap</h2>
    <ol class="rp-langkah">
        <li>Buka menu <b>Kerja Harian › Klien &amp; Penghadap</b>, lalu klik <b>Buat</b>.</li>
        <li>Pilih jenis: orang perseorangan atau badan (PT, CV, yayasan, perkumpulan, koperasi).</li>
        <li>Untuk orang, isi nama sesuai identitas, NIK atau nomor paspor beserta masa berlakunya, tanggal lahir, status kawin, dan alamat. Unggah pindaian KTP pada bagian dokumen.</li>
        <li>Untuk badan, isi nama, nomor SK pengesahan, dan pengurus yang berwenang mewakili.</li>
        <li>Catat hubungan keluarga bila ada. Data ini dipakai untuk memeriksa larangan Pasal 40 dan 52 UUJN.</li>
    </ol>
    @include('panduan.gambar', ['src' => 'klien', 'alt' => 'Daftar klien dan subjek dengan jenis, identitas, dan jumlah berkas', 'ket' => 'Satu klien cukup dicatat sekali dan dapat dipakai di banyak berkas dan akta.'])
    <p class="rp-catatan">Persetujuan klien untuk pemrosesan AI juga dicatat di sini. Tanpa persetujuan itu, AI tidak pernah dipakai untuk data klien tersebut.</p>
</section>

<section id="berkas" class="rp-bagian">
    <h2><span class="rp-nomor">4</span> Berkas pekerjaan</h2>
    <p>Berkas adalah map pekerjaan untuk satu urusan klien, misalnya pendirian PT atau jual beli tanah. Semua akta, dokumen, tugas, tanda terima, dan tagihan menempel pada berkas.</p>
    <ol class="rp-langkah">
        <li>Buka <b>Kerja Harian › Berkas</b>, klik <b>Buat</b>, pilih notaris (jabatan), jenis layanan, dan PIC.</li>
        <li>Tambahkan pihak berkas beserta perannya (penjual, pembeli, kreditur, dan seterusnya).</li>
        <li>Ikuti daftar periksa dokumen yang muncul sesuai jenis layanan, dan tandai setiap dokumen yang sudah diterima.</li>
        <li>Catat <b>tanda terima</b> setiap kali menerima atau menyerahkan dokumen asli.</li>
        <li>Bagi pekerjaan lewat <b>tugas</b> dengan tenggat dan penanggung jawab.</li>
        <li>Pindahkan tahap berkas (Baru, Pengumpulan dokumen, Penyusunan draf, Menunggu persetujuan draf, Dijadwalkan tanda tangan, Ditandatangani, Pascaakta, Selesai) dengan tombol <b>Ubah tahap</b>.</li>
    </ol>
    @include('panduan.gambar', ['src' => 'berkas', 'alt' => 'Halaman berkas dengan tahap, pihak, daftar periksa dokumen, dan tugas', 'ket' => 'Berkas sangat rahasia hanya terlihat oleh notaris, PIC, dan anggota berkas.'])
</section>

<section id="akta" class="rp-bagian">
    <h2><span class="rp-nomor">5</span> Membuat akta</h2>
    <ol class="rp-langkah">
        <li>Dari berkas, klik <b>Tambah akta</b>. Isi judul, jenis akta, rencana tanggal, dan tempat penandatanganan.</li>
        <li>Tambahkan <b>penghadap</b> beserta kedudukannya (untuk diri sendiri, selaku kuasa, atau mewakili badan) dan dua <b>saksi</b> instrumenter.</li>
        <li>Klik <b>Buat draf dari template</b>. Aplikasi menyusun draf .docx dari template yang disetujui notaris: hari, tanggal, jam, terbilang, komparisi, dan saksi terisi otomatis.</li>
        <li>Unduh, sunting di Word bila perlu, lalu unggah kembali dengan <b>Unggah draf hasil suntingan</b>. Setiap versi tersimpan.</li>
        <li>Pada daftar versi draf, buka <b>Temuan</b> atau klik <b>Periksa ulang</b> untuk mencari variabel yang tersisa serta nama dan NIK yang tidak cocok dengan data penghadap.</li>
        <li>Tentukan jadwal dengan <b>Jadwalkan</b>. Jadwal muncul di dasbor notaris.</li>
    </ol>
    @include('panduan.gambar', ['src' => 'akta', 'alt' => 'Halaman akta dengan penghadap, saksi, versi draf, dan tombol tindakan', 'ket' => 'Halaman akta: penghadap, saksi, versi draf, dan riwayat tindakan.'])
</section>

<section id="nomor" class="rp-bagian">
    <h2><span class="rp-nomor">6</span> Memberi nomor akta</h2>
    <p>Setelah akta dibacakan dan ditandatangani, notaris mengklik <b>Catat penandatanganan &amp; beri nomor</b>. Sebelum nomor diberikan, pemeriksaan kesiapan (R-00 sampai R-17) berjalan otomatis.</p>
    <div class="rp-kisi rp-kisi-2">
        <div class="rp-kartu">
            <h3>Temuan kritis</h3>
            <p>Misalnya saksi bersaudara dengan penghadap, penghadap belum cakap, identitas habis berlaku, atau tempat di luar wilayah jabatan. Nomor ditahan sampai diperbaiki, atau notaris mengesampingkan dengan alasan tertulis.</p>
        </div>
        <div class="rp-kartu">
            <h3>Nomor terkunci</h3>
            <p>Nomor bulanan dan nomor urut repertorium diberikan berurutan per notaris. Tidak bisa ganda, melompat, atau mundur. Akta bernomor tidak dapat dihapus.</p>
        </div>
    </div>
    @include('panduan.gambar', ['src' => 'kesiapan', 'alt' => 'Formulir beri nomor dengan temuan kritis saksi bersaudara dengan penghadap', 'ket' => 'Contoh: saksi Nadia Rahma bersaudara dengan penghadap, sehingga nomor ditahan (Pasal 40 UUJN).'])
    <p>Setelah bernomor, isi juga halaman buku, jumlah lampiran, dan sidik jari penghadap. Tenggat pasca akta (misalnya pengesahan PT 60 hari atau pendaftaran fidusia 30 hari) dibuat otomatis.</p>
</section>

<section id="salinan" class="rp-bagian">
    <h2><span class="rp-nomor">7</span> Salinan, pembetulan, dan peminjaman minuta</h2>
    <ul class="rp-centang">
        <li><b>Salinan, kutipan, dan grosse</b>: buka tab <b>Salinan &amp; grosse</b> pada akta, klik <b>Catat salinan/permintaan</b>, lalu isi penerima dan dasar haknya. Permintaan dari pihak yang tidak berhak dicatat sebagai ditolak. Nomor grosse dihitung otomatis.</li>
        <li><b>Berita acara pembetulan</b>: kesalahan tulis pada akta bernomor diperbaiki lewat berita acara, bukan dengan mengubah minuta.</li>
        <li><b>Peminjaman minuta</b>: catat peminjam, dasar permintaan, dan tanggal kembali. Minuta yang lewat jatuh tempo muncul di kesiapan pemeriksaan.</li>
    </ul>
    @include('panduan.gambar', ['src' => 'salinan', 'alt' => 'Daftar salinan, kutipan, dan grosse sebuah akta beserta penerima dan dasar haknya', 'ket' => 'Permintaan salinan dari tetangga yang bukan pihak tetap dicatat, dengan status tidak berhak (ditolak).'])
</section>

<section id="legalisasi" class="rp-bagian">
    <h2><span class="rp-nomor">8</span> Legalisasi dan waarmerking</h2>
    <ol class="rp-langkah">
        <li>Buka <b>Kerja Harian › Legalisasi &amp; Waarmerking</b>, lalu klik <b>Legalisasi baru</b>, <b>Waarmerking baru</b>, atau <b>Pengesahan fotokopi</b>.</li>
        <li>Isi judul surat, tanggal, dan para pihak.</li>
        <li>Nomor N/Leg/KODE/TAHUN dibuat otomatis per jenis dan per tahun.</li>
        <li>Klik <b>Cetak pengesahan</b> untuk mencetak redaksi yang ditempel pada surat.</li>
    </ol>
    @include('panduan.gambar', ['src' => 'legalisasi', 'alt' => 'Daftar legalisasi dan waarmerking dengan nomor otomatis', 'ket' => 'Entri yang salah tidak dihapus, hanya ditandai batal, agar nomor tetap berurutan.'])
</section>

<section id="buku" class="rp-bagian">
    <h2><span class="rp-nomor">9</span> Buku daftar dan laporan MPD</h2>
    <p>Buka <b>Buku Daftar &amp; Protokol › Buku Daftar</b>, pilih notaris dan bulan. Repertorium, klapper, daftar legalisasi, waarmerking, wasiat, protes, dan surat di bawah tangan tersusun dari data yang sudah dicatat.</p>
    <ol class="rp-langkah">
        <li>Periksa setiap buku pada tab masing-masing. Buku yang kosong otomatis bertuliskan NIHIL.</li>
        <li>Klik <b>Paket laporan bulanan MPD</b> untuk mendapat surat pengantar beserta seluruh buku dalam satu PDF A4.</li>
        <li>Setelah dikirim, tandai tenggat laporan bulanan sebagai selesai.</li>
    </ol>
    @include('panduan.gambar', ['src' => 'buku-daftar', 'alt' => 'Halaman buku daftar dengan pilihan notaris, bulan, dan tab repertorium', 'ket' => 'Repertorium bulan lalu siap dicetak tanpa mengetik ulang.'])
    @include('panduan.gambar', ['src' => 'laporan-mpd', 'alt' => 'Surat pengantar laporan bulanan ke Majelis Pengawas Daerah', 'ket' => 'Surat pengantar laporan bulanan ke MPD.'])
</section>

<section id="tenggat" class="rp-bagian">
    <h2><span class="rp-nomor">10</span> Tenggat</h2>
    <p>Menu <b>Kerja Harian › Tenggat</b> berisi semua batas waktu: pengesahan dan perubahan badan hukum, pendaftaran fidusia, daftar wasiat tanggal 5, laporan MPD tanggal 15, dan tenggat yang ditambahkan sendiri. Hari libur nasional dan cuti bersama ikut diperhitungkan.</p>
    @include('panduan.gambar', ['src' => 'tenggat', 'alt' => 'Daftar tenggat dengan status terlambat, mendekati, dan selesai', 'ket' => 'Tenggat terlambat berwarna merah dan tetap tampil sampai ditandai selesai.'])
</section>

<section id="keuangan" class="rp-bagian">
    <h2><span class="rp-nomor">11</span> Tagihan dan dana titipan</h2>
    <ol class="rp-langkah">
        <li>Dari berkas, buat tagihan. Pisahkan baris <b>honorarium</b> dari baris <b>titipan</b> (pajak, PNBP, biaya BPN).</li>
        <li>Aplikasi menghitung batas honorarium Pasal 36 dari nilai ekonomis akta dan memberi peringatan bila terlampaui. Berkas cuma-cuma (Pasal 37) ditandai tanpa honorarium.</li>
        <li>Klik <b>Terbitkan</b>, lalu catat pembayaran (tunai, transfer, atau QRIS). Kuitansi tercetak otomatis.</li>
        <li>Uang titipan masuk ke <b>dana titipan</b> berkas dan setiap pengeluarannya dicatat dengan bukti.</li>
    </ol>
    @include('panduan.gambar', ['src' => 'tagihan', 'alt' => 'Tagihan dengan honorarium dan titipan terpisah serta riwayat pembayaran', 'ket' => 'Honorarium dan titipan terpisah sehingga laporan pendapatan kantor tidak tercampur uang klien.'])
</section>

<section id="protokol" class="rp-bagian">
    <h2><span class="rp-nomor">12</span> Protokol dan pemeriksaan</h2>
    <div class="rp-kisi rp-kisi-2">
        <div class="rp-kartu">
            <h3>Jilid minuta</h3>
            <p>Klik <b>Susun jilid bulan</b>. Minuta dikelompokkan maksimal 50 akta per jilid, lengkap dengan label sampul. Cetak <b>Label sampul</b>, lalu tandai <b>Sudah dijilid</b>.</p>
        </div>
        <div class="rp-kartu">
            <h3>Lokasi arsip dan buku fisik</h3>
            <p>Catat lemari dan rak tempat jilid, repertorium, dan klapper disimpan agar mudah ditemukan.</p>
        </div>
        <div class="rp-kartu">
            <h3>Permintaan penegak hukum</h3>
            <p>Catat permintaan penyidik, penuntut, atau hakim beserta nomor persetujuan MKN (Pasal 66 UUJN).</p>
        </div>
        <div class="rp-kartu">
            <h3>Serah terima protokol</h3>
            <p>Catat protokol yang diterima dari notaris lain atau diserahkan saat cuti, pindah, atau berhenti.</p>
        </div>
    </div>
    @include('panduan.gambar', ['src' => 'jilid', 'alt' => 'Daftar jilid minuta per bulan dengan rentang nomor akta', 'ket' => 'Jilid minuta per bulan dan statusnya.'])
    <p>Halaman <b>Kesiapan Pemeriksaan</b> merangkum yang perlu dibereskan sebelum Majelis Pengawas datang: minuta belum dipindai atau dijilid, sidik jari belum lengkap, laporan terlambat, dan minuta yang dipinjam lewat jatuh tempo.</p>
    @include('panduan.gambar', ['src' => 'kesiapan-pemeriksaan', 'alt' => 'Halaman kesiapan pemeriksaan protokol dengan daftar kekurangan', 'ket' => 'Kerjakan daftar ini setiap bulan, bukan menjelang pemeriksaan.'])
</section>

<section id="pengaturan" class="rp-bagian">
    <h2><span class="rp-nomor">13</span> Pengaturan dan AI</h2>
    <ul class="rp-centang">
        <li><b>Pengaturan kantor</b>: identitas kantor, kop surat, kewajiban MFA, dan pengaturan AI.</li>
        <li><b>Jabatan notaris</b>: nomor SK, wilayah jabatan, dan kode untuk penomoran.</li>
        <li><b>Template akta</b>: staf mengunggah versi baru, notaris menyetujui. Hanya template yang disetujui yang dipakai untuk draf.</li>
        <li><b>Jenis layanan</b>: daftar periksa dokumen dan tenggat bawaan per layanan.</li>
        <li><b>Pengguna</b>: tambah staf, pilih peran, dan reset MFA.</li>
        <li><b>Jejak audit</b>: setiap perubahan tercatat dengan hash berantai, sehingga perubahan diam-diam terlihat.</li>
    </ul>
    @include('panduan.gambar', ['src' => 'pengaturan', 'alt' => 'Halaman pengaturan kantor dengan bagian identitas, keamanan, dan AI', 'ket' => 'AI mati sampai notaris menyalakannya, dan batas biaya bulanan dapat ditetapkan.'])
</section>

<section id="tips" class="rp-bagian">
    <h2><span class="rp-nomor">14</span> Tips dan pertanyaan umum</h2>
    <details class="rp-tanya">
        <summary>Nomor akta salah, bisakah dihapus?</summary>
        <p>Tidak. Akta bernomor tidak dapat dihapus agar repertorium tetap utuh. Kesalahan tulis diperbaiki dengan berita acara pembetulan. Akta yang batal ditandai batal dengan alasan, dan nomornya tetap tercatat.</p>
    </details>
    <details class="rp-tanya">
        <summary>Mengapa tombol beri nomor tidak muncul?</summary>
        <p>Hanya notaris pemilik jabatan yang dapat memberi nomor, dan akta harus sudah memiliki penghadap, saksi, dan draf. Akun tamu juga tidak melihat tombol ini.</p>
    </details>
    <details class="rp-tanya">
        <summary>Bagaimana jika saya bertugas di luar kota?</summary>
        <p>Tempat penandatanganan di luar wilayah jabatan menjadi temuan kritis (Pasal 17 UUJN). Pastikan tempat diisi sesuai kenyataan.</p>
    </details>
    <details class="rp-tanya">
        <summary>Apakah data kantor saya aman bila AI dinyalakan?</summary>
        <p>AI hanya dipakai untuk klien yang memberi persetujuan, tidak pernah untuk berkas sangat rahasia, dan data identitas disamarkan sebelum dikirim. Setiap permintaan AI tercatat beserta biayanya.</p>
    </details>
    <details class="rp-tanya">
        <summary>Apakah data di situs demo sungguhan?</summary>
        <p>Tidak. Semua nama, NIK, dan akta di situs demo adalah data contoh fiktif dan dikembalikan ke keadaan awal setiap hari.</p>
    </details>
</section>

@include('panduan.ajakan', ['halaman' => 'penggunaan'])
