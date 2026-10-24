<?php

namespace App\Services;

use App\Support\Docx;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Template contoh yang disertakan. Wajib ditinjau dan disetujui notaris sebelum dipakai.
 * Setiap paragraf diakhiri tab rata kanan berpengisi strip sehingga sisa baris tertutup garis.
 */
class TemplateBawaan
{
    public function aktaUmum(string $path = 'template/akta-umum-v1.docx'): string
    {
        $w = new PhpWord;
        $w->setDefaultFontName('Times New Roman');
        $w->setDefaultFontSize(12);
        $s = $w->addSection(['pageSizeW' => 11906, 'pageSizeH' => 16838, 'marginLeft' => 2268, 'marginRight' => 1134, 'marginTop' => 1418, 'marginBottom' => 1418]);
        $p = ['spaceAfter' => 0, 'spacing' => 120, 'alignment' => Jc::BOTH];
        $c = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];

        $s->addText('${judul_kapital}', ['bold' => true], $c);
        $s->addText('Nomor: ${nomor_akta}', null, $c);
        $s->addTextBreak();
        foreach ([
            'Pada ${hari_tanggal}.',
            '${jam}.',
            'Berhadapan dengan saya, ${notaris_nama}, Notaris berkedudukan di ${notaris_kedudukan}, dengan dihadiri oleh saksi-saksi yang saya, Notaris, kenal dan akan disebutkan nama-namanya pada bagian akhir akta ini:',
            '${penghadap}',
            '${p_nomor}. ${p_komparisi}',
            '${/penghadap}',
            'Para penghadap telah saya, Notaris, kenal.',
            'Para penghadap menerangkan terlebih dahulu dengan ini:',
            '[Uraikan premis dan isi akta di sini. Ganti paragraf ini dengan isi sesuai jenis akta.]',
            'Pasal 1',
            '[Isi pasal.]',
            'Akhirnya para penghadap menerangkan memilih tempat kedudukan hukum yang umum dan tetap di Kantor Panitera Pengadilan Negeri ${kota_tanda_tangan}.',
            'DEMIKIANLAH AKTA INI',
            'Dibuat sebagai minuta dan dilangsungkan di ${kota_tanda_tangan}, pada hari, tanggal, dan jam sebagaimana disebutkan pada bagian awal akta ini, dengan dihadiri oleh:',
            '${saksi}',
            '${s_nomor}. ${s_uraian}',
            '${/saksi}',
            'sebagai saksi-saksi.',
            '${penutup_pembacaan}',
            '${renvoi}',
            'Minuta akta ini telah ditandatangani dengan sempurna.',
        ] as $baris) {
            $isJudul = in_array($baris, ['Pasal 1', 'DEMIKIANLAH AKTA INI'], true);
            $s->addText($baris, $isJudul ? ['bold' => true] : null, $isJudul ? $c : $p);
        }

        Storage::disk('local')->makeDirectory(dirname($path));
        $full = Storage::disk('local')->path($path);
        $w->save($full);
        Docx::isiGaris($full);

        return $path;
    }
}
