@php
    $beban = [
        ['Penomoran akta dan repertorium', 'Pasal 16 ayat (1) huruf j, Pasal 58 UUJN',
            'Nomor akta dicatat tangan atau di spreadsheet. Nomor ganda, nomor yang terlewat, atau tanggal yang mundur biasanya baru ketahuan saat repertorium disusun di akhir bulan.',
            'Nomor bulanan dan nomor urut repertorium diberikan saat penandatanganan dicatat, dikunci per notaris, dan tidak dapat ganda, melompat, atau mundur. Akta bernomor tidak dapat dihapus; perbaikannya lewat berita acara pembetulan.'],
        ['Pemeriksaan sebelum tanda tangan', 'Pasal 39, 40, 52 UUJN',
            'Notaris mengingat sendiri: apakah penghadap sudah 18 tahun atau sudah kawin, identitasnya masih berlaku, saksinya cukup, dan tidak ada hubungan keluarga yang dilarang.',
            'Pemeriksaan kesiapan (R-00 sampai R-17) berjalan otomatis. Saksi yang bersaudara dengan penghadap, paspor yang habis berlaku, atau tempat tanda tangan di luar wilayah jabatan ditandai kritis dan nomor tidak diberikan sampai diperbaiki atau notaris menuliskan alasannya.'],
        ['Draf akta', 'Pasal 38 UUJN',
            'Draf disalin dari akta lama lalu nama, NIK, tanggal, dan terbilang diganti satu per satu. Sisa data klien sebelumnya sering tertinggal.',
            'Draf .docx dibuat dari template yang disetujui notaris. Hari, tanggal, jam, terbilang, komparisi, dan saksi terisi dari data berkas. Pemeriksa draf mencari variabel yang tersisa serta nama dan NIK yang tidak cocok.'],
        ['Buku daftar dan laporan bulanan MPD', 'Pasal 61 UUJN',
            'Setiap bulan staf mengetik ulang repertorium, klapper, daftar legalisasi, waarmerking, wasiat, dan protes, lalu menyusun surat pengantar ke MPD.',
            'Semua buku daftar tersusun dari data yang sudah dicatat. Pilih notaris dan bulan, lalu cetak A4. Buku yang kosong bertuliskan NIHIL, dan surat pengantar ikut dibuat.'],
        ['Tenggat yang tersebar', 'UU PT, PP 21/2015, PP 24/2016',
            'Tenggat pengesahan PT 60 hari, pendaftaran fidusia 30 hari, daftar wasiat tanggal 5, dan laporan MPD tanggal 15 dicatat di kalender pribadi atau diingat-ingat.',
            'Tenggat dibuat sendiri saat akta bernomor dan setiap awal bulan, dihitung dengan hari kerja dan hari libur, lalu muncul di dasbor tujuh hari sebelumnya. Yang terlambat berwarna merah.'],
        ['Salinan, kutipan, dan grosse', 'Pasal 54 UUJN',
            'Sulit menelusuri siapa yang pernah menerima salinan, apakah ia berhak, dan grosse ke berapa yang sudah dikeluarkan.',
            'Setiap salinan, kutipan, dan grosse dicatat dengan dasar haknya. Permintaan dari pihak yang tidak berhak tetap dicatat sebagai ditolak, dan nomor grosse dihitung otomatis.'],
        ['Legalisasi dan waarmerking', 'Pasal 15 ayat (2) UUJN',
            'Nomor legalisasi dan waarmerking dicatat di buku terpisah, dan redaksi pengesahan diketik ulang setiap kali.',
            'Nomor N/Leg/KODE/TAHUN dibuat otomatis per jenis dan per tahun. Redaksi pengesahan siap cetak, dan entri tidak dapat dihapus, hanya ditandai batal.'],
        ['Honorarium dan uang titipan', 'Pasal 36 dan 37 UUJN',
            'Honorarium bercampur dengan titipan pajak, PNBP, atau biaya BPN. Batas honorarium sulit dipastikan, dan kuitansi ditulis tangan.',
            'Tagihan memisahkan honorarium dari titipan. Batas honorarium dihitung dari nilai ekonomis akta, berkas cuma-cuma ditandai, pembayaran menghasilkan kuitansi, dan dana titipan punya buku sendiri.'],
        ['Protokol dan arsip minuta', 'Pasal 62 sampai 65 UUJN',
            'Minuta dicari di lemari, penjilidan 50 akta per jilid dihitung manual, dan peminjaman minuta tidak tercatat.',
            'Penjilidan disusun otomatis (maksimal 50 minuta per jilid) lengkap dengan label sampul. Lokasi arsip, buku daftar fisik, peminjaman minuta, dan serah terima protokol tercatat.'],
        ['Pemeriksaan MPD dan permintaan penegak hukum', 'Pasal 66 dan 70 UUJN',
            'Kekurangan protokol baru terlihat saat Majelis Pengawas datang. Permintaan penyidik dan persetujuan MKN tersimpan di map surat.',
            'Halaman kesiapan pemeriksaan menunjukkan minuta yang belum dipindai atau dijilid, sidik jari yang belum lengkap, laporan terlambat, dan minuta yang dipinjam melewati jatuh tempo. Permintaan penegak hukum dicatat bersama nomor persetujuan MKN.'],
        ['Rahasia jabatan', 'Pasal 16 ayat (1) huruf f UUJN',
            'Semua staf bisa membuka semua map. Tidak ada jejak siapa yang mengubah data atau mengunduh dokumen.',
            'Akses dibagi per peran (Notaris, Staf, Keuangan, Arsiparis, Admin TI). Berkas sangat rahasia hanya terlihat oleh notaris, PIC, dan anggota berkas. Setiap perubahan masuk jejak audit berantai hash.'],
    ];
@endphp

<p class="rp-lead">Notaris memikul tanggung jawab jabatan yang besar, tetapi sebagian besar waktu kantor habis untuk pekerjaan administrasi yang berulang: menomori akta, menyalin data ke buku daftar, mengingat tenggat, dan menyiapkan laporan bulanan. Ruci Notaria mengambil alih pekerjaan berulang itu, sehingga notaris dan staf dapat memusatkan perhatian pada isi akta dan pelayanan kepada klien.</p>

<div class="rp-kisi rp-kisi-3">
    <div class="rp-kartu rp-sorot">
        <strong>Sekali catat, terpakai di mana-mana</strong>
        <span>Data penghadap yang diisi sekali dipakai untuk draf akta, pemeriksaan kesiapan, repertorium, klapper, dan laporan MPD.</span>
    </div>
    <div class="rp-kartu rp-sorot">
        <strong>Aturan dijaga oleh aplikasi</strong>
        <span>Penomoran, saksi, hubungan keluarga, wilayah jabatan, dan batas honorarium diperiksa otomatis sebelum terlambat.</span>
    </div>
    <div class="rp-kartu rp-sorot">
        <strong>Siap diperiksa kapan saja</strong>
        <span>Buku daftar, protokol, dan jejak audit selalu mutakhir, bukan baru disusun saat MPD datang.</span>
    </div>
</div>

@include('panduan.gambar', ['src' => 'dasbor', 'alt' => 'Dasbor Ruci Notaria berisi tenggat tujuh hari ke depan, jadwal tanda tangan, dan ringkasan bulan ini', 'ket' => 'Dasbor: tenggat, jadwal tanda tangan hari ini dan besok, serta ringkasan kantor dalam satu layar.'])

<h2>Beban yang berkurang</h2>
<p>Setiap baris di bawah membandingkan pekerjaan kantor notaris tanpa aplikasi dengan apa yang dikerjakan Ruci Notaria.</p>

<div class="rp-beban">
    @foreach ($beban as $i => [$judul, $dasar, $tanpa, $dengan])
        <article class="rp-beban-item">
            <header>
                <span class="rp-nomor">{{ $i + 1 }}</span>
                <div>
                    <h3>{{ $judul }}</h3>
                    <p class="rp-dasar">{{ $dasar }}</p>
                </div>
            </header>
            <div class="rp-banding">
                <div class="rp-tanpa">
                    <span class="rp-tag">Tanpa Ruci Notaria</span>
                    <p>{{ $tanpa }}</p>
                </div>
                <div class="rp-dengan">
                    <span class="rp-tag">Dengan Ruci Notaria</span>
                    <p>{{ $dengan }}</p>
                </div>
            </div>
        </article>
    @endforeach
</div>

<div class="rp-kisi rp-kisi-2">
    @include('panduan.gambar', ['src' => 'kesiapan', 'alt' => 'Pemeriksaan kesiapan menandai saksi yang bersaudara dengan penghadap sebagai temuan kritis', 'ket' => 'Saksi bersaudara dengan penghadap: nomor akta ditahan (Pasal 40 UUJN).'])
    @include('panduan.gambar', ['src' => 'laporan-mpd', 'alt' => 'Surat pengantar laporan bulanan ke Majelis Pengawas Daerah beserta daftar buku', 'ket' => 'Laporan bulanan MPD tersusun dari akta dan surat yang sudah dicatat.'])
</div>

<h2>Risiko yang dicegah</h2>
<ul class="rp-centang">
    <li>Nomor akta ganda atau terlewat yang membuat repertorium tidak sesuai dengan minuta.</li>
    <li>Akta yang dibuat dengan saksi atau penghadap yang dilarang oleh Pasal 40 dan Pasal 52 UUJN.</li>
    <li>Laporan bulanan, daftar wasiat, atau pendaftaran fidusia dan pengesahan badan hukum yang terlambat.</li>
    <li>Salinan akta yang diberikan kepada pihak yang tidak berhak.</li>
    <li>Honorarium yang melampaui batas Pasal 36, atau uang titipan klien yang tercampur dengan pendapatan kantor.</li>
    <li>Minuta yang hilang, dipinjam tanpa catatan, atau belum dijilid saat pemeriksaan protokol.</li>
    <li>Perubahan data tanpa jejak, atau data rahasia jabatan yang terbuka untuk semua staf.</li>
</ul>

<h2>Siapa yang paling merasakan</h2>
<div class="rp-kisi rp-kisi-2">
    <div class="rp-kartu">
        <h3>Notaris</h3>
        <p>Melihat seluruh kantor dari dasbor, memberi nomor akta setelah pemeriksaan kesiapan bersih, menyetujui template, dan memastikan kantor siap diperiksa MPD.</p>
    </div>
    <div class="rp-kartu">
        <h3>Staf dan drafter</h3>
        <p>Mencatat klien dan berkas sekali, membuat draf dari template, mengikuti daftar periksa dokumen, dan tidak lagi menyalin data ke buku daftar.</p>
    </div>
    <div class="rp-kartu">
        <h3>Keuangan</h3>
        <p>Menerbitkan tagihan yang memisahkan honorarium dan titipan, mencetak kuitansi, dan mempertanggungjawabkan dana titipan per berkas.</p>
    </div>
    <div class="rp-kartu">
        <h3>Arsiparis</h3>
        <p>Menyusun jilid minuta, mencatat lokasi arsip dan buku daftar fisik, mengelola peminjaman minuta, dan serah terima protokol.</p>
    </div>
</div>

<h2>Yang tetap menjadi keputusan notaris</h2>
<p>Ruci Notaria membantu kepatuhan dan mengurangi kesalahan administrasi, tetapi tidak menggantikan pertimbangan notaris. Isi dan redaksi akta, persetujuan template, keputusan mengesampingkan temuan, dan penggunaan AI tetap berada di tangan notaris. AI mati sampai notaris menyalakannya, hanya dipakai untuk klien yang setuju, dan tidak pernah untuk berkas sangat rahasia.</p>

@include('panduan.ajakan', ['halaman' => 'mengapa'])
