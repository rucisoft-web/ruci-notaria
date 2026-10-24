<?php

namespace App\Services;

use App\Enums\JenisSurat;
use App\Enums\KategoriAkta;
use App\Enums\Kedudukan;
use App\Models\Akta;
use App\Models\AktaPenghadap;
use App\Models\SuratBawahTangan;
use Illuminate\Support\Collection;

/**
 * Menyusun isi buku daftar wajib dari data akta dan surat yang sudah tercatat (Pasal 58 sampai 59 UUJN).
 * Buku daftar tidak pernah diketik terpisah; selalu dihasilkan dari register.
 */
class BukuDaftar
{
    /** Uraian nama penghadap beserta kedudukannya untuk kolom repertorium. */
    public static function uraianPenghadap(AktaPenghadap $p): string
    {
        $nama = $p->data()['nama'] ?? $p->subjek->nama;

        return match ($p->kedudukan) {
            Kedudukan::DiriSendiri => $nama,
            Kedudukan::Kuasa => $nama.', selaku kuasa'.($p->mewakili ? ' dari '.$p->mewakili->nama : ''),
            Kedudukan::MewakiliBadan => $nama.', mewakili '.($p->mewakili?->nama ?? 'badan'),
        };
    }

    /** @return Collection<int, array<string, mixed>> */
    public function repertorium(int $jabatanId, int $tahun, int $bulan): Collection
    {
        return Akta::query()->periode($jabatanId, $tahun, $bulan)->bernomor()
            ->with('penghadap.subjek', 'penghadap.mewakili')->orderBy('nomor_urut')->get()
            ->map(fn (Akta $a) => [
                'nomor_urut' => $a->nomor_urut,
                'nomor_bulanan' => $a->nomor_bulanan,
                'tanggal' => $a->tanggal,
                'jam' => substr((string) $a->jam, 0, 5),
                'sifat' => $a->judul.($a->sifat->value === 'originali' ? ' (originali)' : ''),
                'penghadap' => $a->penghadap->map(fn ($p) => static::uraianPenghadap($p))->all(),
                'halaman' => $a->halaman_buku,
                'akta' => $a,
            ]);
    }

    /** Kunci urut nama: abaikan gelar di depan dan di belakang koma. */
    public static function kunciUrut(string $nama): string
    {
        $nama = preg_replace('/,.*$/', '', $nama);
        $nama = preg_replace('/^((prof|dr|drs|dra|ir|h|hj|tn|ny|nn|sdr|sdri)\.?\s+)+/i', '', trim($nama));

        return mb_strtolower(trim($nama));
    }

    /** Klapper: nama penghadap menurut abjad (Pasal 59 UUJN). */
    public function klapper(int $jabatanId, int $tahun, int $bulan): Collection
    {
        return $this->repertorium($jabatanId, $tahun, $bulan)
            ->flatMap(fn ($row) => $row['akta']->penghadap->map(fn ($p) => [
                'nama' => $p->data()['nama'] ?? $p->subjek->nama,
                'sifat' => $row['sifat'],
                'nomor_bulanan' => $row['nomor_bulanan'],
                'tanggal' => $row['tanggal'],
            ]))
            ->sortBy(fn ($r) => static::kunciUrut($r['nama']).'|'.str_pad((string) $r['nomor_bulanan'], 6, '0', STR_PAD_LEFT))
            ->values()
            ->groupBy(fn ($r) => mb_strtoupper(mb_substr(static::kunciUrut($r['nama']), 0, 1)));
    }

    public function surat(JenisSurat $jenis, int $jabatanId, int $tahun, int $bulan): Collection
    {
        return SuratBawahTangan::query()->where('jabatan_id', $jabatanId)->where('jenis', $jenis->value)
            ->where('tahun', $tahun)->whereMonth('tanggal', $bulan)->orderBy('nomor_urut')->get();
    }

    /** @param  list<KategoriAkta>  $kategori */
    public function aktaKategori(array $kategori, int $jabatanId, int $tahun, int $bulan): Collection
    {
        return $this->repertorium($jabatanId, $tahun, $bulan)
            ->filter(fn ($row) => in_array($row['akta']->kategori, $kategori, true))->values();
    }

    public function wasiat(int $jabatanId, int $tahun, int $bulan): Collection
    {
        return $this->aktaKategori([KategoriAkta::Wasiat, KategoriAkta::WasiatBawahTangan], $jabatanId, $tahun, $bulan);
    }

    public function protes(int $jabatanId, int $tahun, int $bulan): Collection
    {
        return $this->aktaKategori([KategoriAkta::Protes], $jabatanId, $tahun, $bulan);
    }

    public const JENIS = [
        'repertorium' => 'Daftar akta (repertorium)',
        'klapper' => 'Daftar nama penghadap (klapper)',
        'legalisasi' => 'Daftar surat di bawah tangan yang disahkan (legalisasi)',
        'waarmerking' => 'Daftar surat di bawah tangan yang dibukukan (waarmerking)',
        'fotokopi' => 'Daftar pengesahan kecocokan fotokopi',
        'wasiat' => 'Daftar akta wasiat',
        'protes' => 'Daftar protes',
    ];

    /** Isi satu buku daftar untuk satu bulan; dipakai layar dan cetakan supaya keduanya selalu sama. */
    public function isi(string $jenis, int $jabatanId, int $tahun, int $bulan): Collection
    {
        return match ($jenis) {
            'repertorium' => $this->repertorium($jabatanId, $tahun, $bulan),
            'klapper' => $this->klapper($jabatanId, $tahun, $bulan),
            'legalisasi' => $this->surat(JenisSurat::Legalisasi, $jabatanId, $tahun, $bulan),
            'waarmerking' => $this->surat(JenisSurat::Waarmerking, $jabatanId, $tahun, $bulan),
            'fotokopi' => $this->surat(JenisSurat::Fotokopi, $jabatanId, $tahun, $bulan),
            'wasiat' => $this->wasiat($jabatanId, $tahun, $bulan),
            'protes' => $this->protes($jabatanId, $tahun, $bulan),
            default => throw new \InvalidArgumentException("Jenis buku daftar tidak dikenal: {$jenis}"),
        };
    }
}
