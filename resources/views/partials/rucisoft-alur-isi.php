<?php

// Isi blok halaman awal seragam (partials/rucisoft-alur.blade.php) khusus produk ini.
return [
    'slug' => 'ruci-notaria',
    'nama' => 'Ruci Notaria',
    'aksen' => '#0b3a5c',
    'aksen_2' => '#c9963e',
    'coba_url' => '#demo',
    'masalah_judul' => 'Pekerjaan administrasi menyita waktu notaris yang seharusnya untuk klien.',
    'masalah_lead' => 'Kantor notaris bekerja dengan tenggat dan aturan yang ketat. Kesalahan kecil di administrasi bisa menjadi temuan saat pemeriksaan MPD.',
    'masalah' => [
        ['Nomor akta', 'Nomor ganda, celah, atau tanggal yang mundur baru ketahuan saat repertorium disusun.'],
        ['Laporan bulanan', 'Repertorium, klapper, legalisasi, waarmerking, wasiat, dan surat pengantar MPD diketik ulang setiap bulan.'],
        ['Tenggat', 'Pengesahan PT 60 hari, fidusia 30 hari, daftar wasiat tanggal 5, laporan MPD tanggal 15, mudah terlewat.'],
        ['Uang klien', 'Honorarium dan titipan pajak atau PNBP bercampur, sehingga sulit dipertanggungjawabkan.'],
    ],
    'penyebab_judul' => 'Data kantor dicatat berulang di tempat yang berbeda.',
    'penyebab_lead' => 'Buku, Excel, dan berkas kertas berjalan sendiri-sendiri, sehingga aturan UUJN diperiksa dengan ingatan, bukan dengan sistem.',
    'penyebab' => [
        ['Penomoran manual', 'Nomor akta diberikan tanpa pemeriksaan otomatis terhadap urutan dan tanggal.'],
        ['Register disusun ulang', 'Buku daftar dan laporan MPD diketik dari nol, bukan dari akta yang sudah tercatat.'],
        ['Tenggat tidak dihitung sistem', 'Batas waktu AHU dan PPAT dihitung manual dengan hari kalender.'],
        ['Keuangan tidak dipisah', 'Honorarium dan titipan dicatat di buku yang sama tanpa saldo per klien.'],
    ],
    'solusi_judul' => 'Ruci Notaria membuat kantor rapi, patuh UUJN, dan siap diperiksa MPD.',
    'solusi_lead' => 'Setiap berkas dilacak dari penerimaan klien sampai protokol, dengan aturan UUJN diperiksa oleh aplikasi.',
    'solusi' => [
        ['Cek kesiapan akta & nomor terkunci', 'Aplikasi menolak memberi nomor bila ada yang melanggar UUJN.', 'Nomor akta'],
        ['Register & laporan MPD otomatis', 'Buku daftar dan laporan bulanan tersusun dari akta yang sudah bernomor.', 'Laporan bulanan'],
        ['Pengingat tenggat AHU & PPAT', 'Batas waktu dihitung dengan hari kerja dan diingatkan sebelum jatuh tempo.', 'Tenggat'],
        ['Honorarium & titipan terpisah', 'Tagihan, titipan, dan saldo per klien tercatat dan bisa dipertanggungjawabkan.', 'Uang klien'],
    ],
];
