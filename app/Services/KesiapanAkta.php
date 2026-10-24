<?php

namespace App\Services;

use App\Models\Akta;
use Illuminate\Support\Carbon;

/**
 * Pemeriksaan deterministik sebelum akta ditandatangani dan diberi nomor.
 * Temuan "kritis" menghalangi penomoran kecuali notaris mengesampingkan dengan alasan tercatat.
 *
 * @phpstan-type Temuan array{tingkat: string, kode: string, pesan: string}
 */
class KesiapanAkta
{
    public function __construct(private PemeriksaHubungan $hubungan) {}

    /** @return list<Temuan> */
    public function periksa(Akta $akta, ?Carbon $tanggal = null): array
    {
        $tanggal ??= $akta->jadwal_at ? Carbon::parse($akta->jadwal_at) : now();
        $akta->loadMissing('penghadap.subjek', 'saksi.subjek', 'jabatan', 'drafTerakhir');
        $t = [];
        $add = function (string $tingkat, string $kode, string $pesan) use (&$t) {
            $t[] = compact('tingkat', 'kode', 'pesan');
        };

        if ($akta->penghadap->isEmpty()) {
            $add('kritis', 'R-00', 'Belum ada penghadap.');
        }

        foreach ($akta->penghadap as $p) {
            $s = $p->subjek;
            if ($s->isBadan()) {
                $add('kritis', 'R-00', "{$s->nama} adalah badan; penghadap harus orang yang mewakilinya.");

                continue;
            }
            if (blank($s->nik)) {
                $add('kritis', 'R-06', "NIK/nomor identitas {$s->nama} belum diisi.");
            }
            $umur = $s->umurPada($tanggal);
            if ($umur === null) {
                $add('perlu_dicek', 'R-02', "Tanggal lahir {$s->nama} belum diisi; kecakapan umur tidak dapat diperiksa.");
            } elseif ($umur < 18 && $s->status_perkawinan !== 'kawin') {
                $add('kritis', 'R-02', "{$s->nama} berumur {$umur} tahun dan belum kawin (Pasal 39 UUJN).");
            }
            if ($s->identitas_berlaku_sampai && $s->identitas_berlaku_sampai->lt($tanggal->copy()->startOfDay())) {
                $add('kritis', 'R-14', "Identitas {$s->nama} habis berlaku {$s->identitas_berlaku_sampai->format('d/m/Y')}.");
            }
            if ($p->kedudukan?->value !== 'diri_sendiri' && blank($p->keterangan_kedudukan)) {
                $add('perlu_dicek', 'R-09', "Dasar kedudukan {$s->nama} ({$p->kedudukan->getLabel()}) belum diuraikan.");
            }
        }

        $saksiAkta = $akta->saksi->where('jenis', 'akta');
        $minimum = $akta->kategori->minimumSaksi();
        if ($saksiAkta->count() < $minimum) {
            $add('kritis', 'R-03', "Saksi akta {$saksiAkta->count()} orang; minimal {$minimum} (Pasal 16 ayat 1 huruf m UUJN).");
        }
        foreach ($akta->saksi as $s) {
            $umur = $s->subjek->umurPada($tanggal);
            if ($umur !== null && $umur < 18) {
                $add('kritis', 'R-03', "Saksi {$s->subjek->nama} belum berumur 18 tahun (Pasal 40 UUJN).");
            }
        }

        foreach ($this->hubungan->periksaAkta($akta) as $temuan) {
            $t[] = $temuan;
        }

        $jabatan = $akta->jabatan;
        if ($akta->provinsi_tanda_tangan && $jabatan && mb_strtolower(trim($akta->provinsi_tanda_tangan)) !== mb_strtolower(trim($jabatan->provinsi))) {
            $add('kritis', 'R-13', "Tempat penandatanganan di {$akta->provinsi_tanda_tangan}, di luar wilayah jabatan ({$jabatan->provinsi}) (Pasal 17 UUJN).");
        }
        if ($jabatan?->sedangCuti($tanggal)) {
            $add('kritis', 'R-15', "{$jabatan->nama_lengkap} sedang cuti pada tanggal tersebut; akta dibuat oleh notaris pengganti.");
        }
        if ($jabatan?->batasUsia() && $jabatan->batasUsia()->lte($tanggal)) {
            $add('perlu_dicek', 'R-16', 'Notaris telah mencapai usia 65 tahun; pastikan ada perpanjangan masa jabatan (Pasal 8 UUJN).');
        }

        if ($akta->sifat->value === 'minuta' && ! $akta->drafTerakhir?->final) {
            $add('perlu_dicek', 'R-17', 'Belum ada draf yang ditandai final.');
        }
        if ($akta->drafTerakhir && $akta->drafTerakhir->jumlahTemuanKritis() > 0) {
            $add('perlu_dicek', 'R-01', 'Draf terakhir masih memiliki temuan kritis dari pemeriksa draf.');
        }

        return $t;
    }

    public static function adaKritis(array $temuan): bool
    {
        return collect($temuan)->contains('tingkat', 'kritis');
    }
}
