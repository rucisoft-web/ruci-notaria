<?php

namespace App\Services;

use App\Models\Akta;
use App\Models\PeminjamanMinuta;
use App\Models\Tenggat;

/** Daftar celah yang biasanya ditemukan saat pemeriksaan protokol oleh MPD (Pasal 70 UUJN). */
class KesiapanPemeriksaan
{
    /** @return list<array{judul: string, jumlah: int, contoh: list<string>}> */
    public function periksa(int $jabatanId, int $tahun): array
    {
        $minuta = fn () => Akta::query()->where('jabatan_id', $jabatanId)->where('tahun', $tahun)->bernomor()->where('sifat', 'minuta');
        $bulanLalu = now()->startOfMonth();
        $contoh = fn ($q) => $q->orderBy('nomor_urut')->limit(8)->get()->map(fn (Akta $a) => 'No. '.$a->nomor_bulanan.' ('.$a->tanggal->format('d/m/Y').') '.$a->judul)->all();

        $baris = [
            ['Minuta belum dipindai (cadangan digital)', $minuta()->whereNull('minuta_scan_path')],
            ['Lampiran minuta belum lengkap', $minuta()->where('minuta_lampiran_lengkap', false)],
            ['Sidik jari penghadap belum lengkap', $minuta()->where('minuta_sidik_jari_lengkap', false)],
            ['Minuta bulan yang sudah lewat belum masuk jilid', $minuta()->whereNull('jilid_minuta_id')->where('tanggal', '<', $bulanLalu)],
            ['Entri repertorium belum dicatat halaman buku fisiknya', Akta::query()->where('jabatan_id', $jabatanId)->where('tahun', $tahun)->bernomor()->whereNull('halaman_buku')],
        ];

        $hasil = [];
        foreach ($baris as [$judul, $query]) {
            $hasil[] = ['judul' => $judul, 'jumlah' => (clone $query)->count(), 'contoh' => $contoh(clone $query)];
        }

        $telat = Tenggat::query()->where('jabatan_id', $jabatanId)->whereIn('kode', ['T-MPD', 'T-WST', 'T-JLD'])
            ->where('periode', 'like', $tahun.'-%')
            ->where(fn ($q) => $q->where(fn ($x) => $x->whereNull('selesai_at')->whereDate('jatuh_tempo', '<', today()))
                ->orWhereColumn('selesai_at', '>', 'jatuh_tempo'));
        $hasil[] = ['judul' => 'Laporan bulanan/wasiat/penjilidan yang terlambat', 'jumlah' => (clone $telat)->count(),
            'contoh' => (clone $telat)->limit(8)->pluck('judul')->all()];

        $pinjam = PeminjamanMinuta::query()->whereNull('dikembalikan_at')->whereDate('jatuh_tempo', '<', today())->with('akta');
        $hasil[] = ['judul' => 'Minuta dipinjam melewati jatuh tempo', 'jumlah' => (clone $pinjam)->count(),
            'contoh' => (clone $pinjam)->limit(8)->get()->map(fn ($p) => $p->akta->label())->all()];

        return $hasil;
    }

    /** Celah nomor (seharusnya tidak pernah terjadi karena penomoran terkunci; diperiksa sebagai pengaman). */
    public function celahNomor(int $jabatanId, int $tahun): array
    {
        $nomor = Akta::query()->where('jabatan_id', $jabatanId)->where('tahun', $tahun)->bernomor()->orderBy('nomor_urut')->pluck('nomor_urut')->all();
        if ($nomor === []) {
            return [];
        }

        return array_values(array_diff(range(1, max($nomor)), $nomor));
    }
}
