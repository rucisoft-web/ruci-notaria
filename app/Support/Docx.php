<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use ZipArchive;

/** Operasi kecil pada berkas .docx tanpa Word: ambil teks, ubah tab, dan isi garis akhir paragraf. */
class Docx
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    /** Teks polos per paragraf, dipakai pemeriksa draf. */
    public static function teks(string $path): string
    {
        $xml = static::baca($path);
        $xml = preg_replace(['#<w:tab/>#', '#</w:p>#', '#<w:br/>#'], ["\t", "\n", "\n"], $xml);

        return html_entity_decode(trim(strip_tags($xml)), ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    /**
     * Mengisi sisa setiap baris akhir paragraf dengan tanda strip sampai margin kanan (kebiasaan penulisan akta
     * agar tidak ada ruang kosong, Pasal 42 UUJN). Caranya: tab rata kanan berpengisi strip + satu tab di akhir paragraf.
     * Paragraf kosong, judul rata tengah, isi tabel, dan paragraf yang sudah berakhir dengan tab dilewati.
     */
    public static function isiGaris(string $path): int
    {
        $dom = static::dom($path);
        $xp = new DOMXPath($dom);
        $xp->registerNamespace('w', self::W);
        $posisi = static::lebarTeks($xp);
        $diubah = 0;

        foreach ($xp->query('//w:body/w:p') as $p) {
            /** @var DOMElement $p */
            $teks = trim(implode('', array_map(fn ($n) => $n->textContent, iterator_to_array($xp->query('.//w:t', $p)))));
            if ($teks === '' || $xp->query('./w:pPr/w:jc[@w:val="center"]', $p)->length > 0) {
                continue;
            }
            $runs = $xp->query('./w:r', $p);
            $last = $runs->length ? $runs->item($runs->length - 1) : null;
            if ($last && $xp->query('./w:tab', $last)->length > 0 && $last->lastChild?->localName === 'tab') {
                continue;
            }

            $pPr = $xp->query('./w:pPr', $p)->item(0);
            if (! $pPr) {
                $pPr = $dom->createElementNS(self::W, 'w:pPr');
                $p->insertBefore($pPr, $p->firstChild);
            }
            $tabs = $xp->query('./w:tabs', $pPr)->item(0);
            if (! $tabs) {
                $tabs = $dom->createElementNS(self::W, 'w:tabs');
                // w:tabs harus berada sebelum elemen pPr lain kecuali pStyle/keepNext/... ; letakkan setelah pStyle bila ada.
                $style = $xp->query('./w:pStyle|./w:keepNext|./w:keepLines|./w:pageBreakBefore|./w:framePr|./w:widowControl|./w:numPr|./w:suppressLineNumbers|./w:pBdr|./w:shd', $pPr);
                $ref = $style->length ? $style->item($style->length - 1)->nextSibling : $pPr->firstChild;
                $pPr->insertBefore($tabs, $ref);
            }
            $tab = $dom->createElementNS(self::W, 'w:tab');
            $tab->setAttributeNS(self::W, 'w:val', 'right');
            $tab->setAttributeNS(self::W, 'w:leader', 'hyphen');
            $tab->setAttributeNS(self::W, 'w:pos', (string) $posisi);
            $tabs->appendChild($tab);

            $r = $dom->createElementNS(self::W, 'w:r');
            if ($last && ($rPr = $xp->query('./w:rPr', $last)->item(0))) {
                $r->appendChild($rPr->cloneNode(true));
            }
            $r->appendChild($dom->createElementNS(self::W, 'w:tab'));
            $p->appendChild($r);
            $diubah++;
        }

        static::tulis($path, $dom->saveXML());

        return $diubah;
    }

    /** Karakter tab di dalam w:t diubah menjadi elemen w:tab agar Word menampilkannya sebagai tab. */
    public static function normalisasiTab(string $path): void
    {
        $xml = static::baca($path);
        $baru = preg_replace_callback('#<w:t(\s[^>]*)?>([^<]*)</w:t>#', function ($m) {
            if (! str_contains($m[2], "\t")) {
                return $m[0];
            }
            $bagian = explode("\t", $m[2]);

            return implode('<w:tab/>', array_map(fn ($b) => '<w:t xml:space="preserve">'.$b.'</w:t>', $bagian));
        }, $xml);
        $baru = str_replace('<w:t xml:space="preserve"></w:t>', '', $baru);

        if ($baru !== $xml) {
            static::tulis($path, $baru);
        }
    }

    private static function lebarTeks(DOMXPath $xp): int
    {
        $sect = $xp->query('//w:body/w:sectPr')->item(0);
        $w = $sect ? (float) $xp->evaluate('string(./w:pgSz/@w:w)', $sect) : 0;
        $l = $sect ? (float) $xp->evaluate('string(./w:pgMar/@w:left)', $sect) : 0;
        $r = $sect ? (float) $xp->evaluate('string(./w:pgMar/@w:right)', $sect) : 0;

        return $w > 0 ? (int) round($w - $l - $r) : 9026;
    }

    private static function baca(string $path): string
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Berkas .docx tidak dapat dibuka.');
        }
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if ($xml === false) {
            throw new RuntimeException('Berkas bukan dokumen Word yang sah.');
        }

        return $xml;
    }

    private static function dom(string $path): DOMDocument
    {
        $dom = new DOMDocument;
        $dom->preserveWhiteSpace = true;
        $dom->loadXML(static::baca($path), LIBXML_NONET);

        return $dom;
    }

    private static function tulis(string $path, string $xml): void
    {
        $zip = new ZipArchive;
        $zip->open($path);
        $zip->addFromString('word/document.xml', $xml);
        $zip->close();
    }
}
