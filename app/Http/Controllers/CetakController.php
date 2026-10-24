<?php

namespace App\Http\Controllers;

use App\Filament\Pages\BukuDaftar as HalamanBuku;
use App\Models\Akta;
use App\Models\Berkas;
use App\Models\Jabatan;
use App\Models\JilidMinuta;
use App\Models\Pembayaran;
use App\Models\Pengaturan;
use App\Models\SuratBawahTangan;
use App\Models\Tagihan;
use App\Models\TandaTerima;
use App\Models\User;
use App\Services\BukuDaftar;
use Illuminate\Contracts\View\View;

/** Halaman siap cetak (A4). Pengguna mencetak atau menyimpan sebagai PDF dari peramban. */
class CetakController extends Controller
{
    private function user(): User
    {
        /** @var User */
        return auth()->user();
    }

    private function bolehLihatBerkas(?int $berkasId): void
    {
        abort_unless($this->user()->role->canViewFiles(), 403);
        if ($berkasId) {
            abort_unless(Berkas::query()->terlihatOleh($this->user())->whereKey($berkasId)->exists(), 403);
        }
    }

    private function bolehKeuangan(Tagihan $tagihan): void
    {
        abort_unless($this->user()->role->canViewFinance(), 403);
        $this->bolehLihatBerkas($tagihan->berkas_id);
    }

    private function periode(int $tahun, int $bulan): void
    {
        abort_unless($bulan >= 1 && $bulan <= 12 && $tahun >= 2000 && $tahun <= 2100, 404);
    }

    public function buku(string $jenis, Jabatan $jabatan, int $tahun, int $bulan, BukuDaftar $buku): View
    {
        $this->bolehLihatBerkas(null);
        $this->periode($tahun, $bulan);
        abort_unless(isset(BukuDaftar::JENIS[$jenis]), 404);

        return view('cetak.buku', [
            'jabatan' => $jabatan, 'tahun' => $tahun, 'bulan' => $bulan, 'namaBulan' => HalamanBuku::BULAN[$bulan],
            'buku' => [['jenis' => $jenis, 'judul' => BukuDaftar::JENIS[$jenis], 'isi' => $buku->isi($jenis, $jabatan->id, $tahun, $bulan)]],
            'pengantar' => false,
        ]);
    }

    /** Paket laporan bulanan ke MPD: surat pengantar dan salinan semua buku daftar bulan itu (Pasal 61 UUJN). */
    public function laporanMpd(Jabatan $jabatan, int $tahun, int $bulan, BukuDaftar $buku): View
    {
        $this->bolehLihatBerkas(null);
        $this->periode($tahun, $bulan);

        return view('cetak.buku', [
            'jabatan' => $jabatan, 'tahun' => $tahun, 'bulan' => $bulan, 'namaBulan' => HalamanBuku::BULAN[$bulan],
            'buku' => collect(BukuDaftar::JENIS)->map(fn ($judul, $jenis) => ['jenis' => $jenis, 'judul' => $judul, 'isi' => $buku->isi($jenis, $jabatan->id, $tahun, $bulan)])->values()->all(),
            'pengantar' => true,
        ]);
    }

    public function tandaTerima(TandaTerima $tandaTerima): View
    {
        $this->bolehLihatBerkas($tandaTerima->berkas_id);

        return view('cetak.tanda-terima', ['tt' => $tandaTerima->load('berkas.klien', 'berkas.jabatan', 'createdBy')]);
    }

    public function lembarTtd(Akta $akta): View
    {
        $this->bolehLihatBerkas($akta->berkas_id);

        return view('cetak.lembar-ttd', ['akta' => $akta->load('penghadap.subjek', 'penghadap.mewakili', 'saksi.subjek', 'jabatan', 'berkas')]);
    }

    public function pengesahan(SuratBawahTangan $surat): View
    {
        $this->bolehLihatBerkas($surat->berkas_id);

        return view('cetak.pengesahan', ['s' => $surat->load('jabatan')]);
    }

    public function labelJilid(JilidMinuta $jilid): View
    {
        $this->bolehLihatBerkas(null);

        return view('cetak.label-jilid', ['j' => $jilid->load('jabatan'), 'namaBulan' => HalamanBuku::BULAN[$jilid->bulan]]);
    }

    public function tagihan(Tagihan $tagihan): View
    {
        $this->bolehKeuangan($tagihan);

        return view('cetak.tagihan', ['t' => $tagihan->load('items', 'pembayaran', 'berkas.klien', 'berkas.jabatan')]);
    }

    public function kuitansi(Pembayaran $pembayaran): View
    {
        $this->bolehKeuangan($pembayaran->tagihan);

        return view('cetak.kuitansi', ['p' => $pembayaran->load('tagihan.berkas.klien', 'tagihan.berkas.jabatan')]);
    }

    public static function kantor(): array
    {
        return ['nama' => Pengaturan::get('kantor.nama'), 'kota' => Pengaturan::get('kantor.kota'), 'telepon' => Pengaturan::get('kantor.telepon')];
    }
}
