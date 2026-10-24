<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/** Halaman publik "Mengapa Ruci Notaria" dan "Panduan penggunaan", ditautkan dari halaman login. */
class PanduanController extends Controller
{
    public function mengapa(): View
    {
        return view('panduan.publik', ['isi' => 'panduan.mengapa', 'judul' => 'Mengapa notaris memerlukan Ruci Notaria',
            'ringkas' => 'Beban kerja notaris yang berkurang dengan Ruci Notaria: penomoran akta, buku daftar, laporan MPD, tenggat, salinan, keuangan, dan protokol.']);
    }

    public function penggunaan(): View
    {
        return view('panduan.publik', ['isi' => 'panduan.penggunaan', 'judul' => 'Panduan penggunaan Ruci Notaria',
            'ringkas' => 'Langkah demi langkah memakai Ruci Notaria, dari klien dan berkas sampai akta bernomor, buku daftar, laporan MPD, keuangan, dan protokol.']);
    }
}
