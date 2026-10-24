<?php

namespace App\Services;

use App\Models\Akta;
use App\Models\Subjek;

/**
 * Pemeriksaan hubungan keluarga yang dilarang, berdasarkan relasi yang tercatat:
 * - Pasal 52 UUJN: notaris dengan penghadap (suami/istri, sedarah/semenda garis lurus tanpa batas, garis samping s.d. derajat 3).
 * - Pasal 40 UUJN: saksi dengan notaris atau para pihak (perkawinan, darah garis lurus tanpa batas, garis samping s.d. derajat 3).
 * Kesamaan nama keluarga hanya menjadi peringatan "perlu dicek" karena banyak nama Indonesia tidak memakai nama keluarga.
 */
class PemeriksaHubungan
{
    public function dilarangPasal52(Subjek $notaris, Subjek $pihak): ?string
    {
        $h = $notaris->hubunganDengan($pihak);
        if (! $h) {
            return null;
        }
        ['garis' => $garis, 'derajat' => $derajat] = $h->garis();

        return match (true) {
            $garis === 'perkawinan', $garis === 'lurus', $garis === 'semenda_lurus' => $h->getLabel(),
            in_array($garis, ['samping', 'semenda_samping'], true) && $derajat <= 3 => $h->getLabel(),
            default => null,
        };
    }

    public function dilarangPasal40(Subjek $saksi, Subjek $lain): ?string
    {
        $h = $saksi->hubunganDengan($lain);
        if (! $h) {
            return null;
        }
        ['garis' => $garis, 'derajat' => $derajat] = $h->garis();

        return match (true) {
            $garis === 'perkawinan', $garis === 'lurus' => $h->getLabel(),
            $garis === 'samping' && $derajat <= 3 => $h->getLabel(),
            default => null,
        };
    }

    public static function namaKeluarga(string $nama): ?string
    {
        $bersih = trim(preg_replace('/\b(dr|drs|ir|h|hj|prof|s\.?h|m\.?kn|s\.?e|m\.?m|s\.?t|s\.?kom|mba|m\.?h)\.?\b/i', '', preg_replace('/,.*$/', '', $nama)));
        $kata = preg_split('/\s+/', $bersih);

        return count($kata) >= 2 ? mb_strtolower(end($kata)) : null;
    }

    /**
     * @return list<array{tingkat: string, kode: string, pesan: string}>
     */
    public function periksaAkta(Akta $akta): array
    {
        $temuan = [];
        $akta->loadMissing('penghadap.subjek', 'saksi.subjek', 'jabatan.subjek');
        $notaris = $akta->jabatan?->subjek;
        $pihak = $akta->penghadap->pluck('subjek')->filter();

        foreach ($pihak as $p) {
            if ($notaris && ($h = $this->dilarangPasal52($notaris, $p))) {
                $temuan[] = ['tingkat' => 'kritis', 'kode' => 'R-05', 'pesan' => "Notaris memiliki hubungan \"{$h}\" dengan penghadap {$p->nama} (Pasal 52 UUJN)."];
            }
        }

        foreach ($akta->saksi as $s) {
            $saksi = $s->subjek;
            foreach ($pihak->concat([$notaris])->filter() as $lain) {
                if ($saksi->id === $lain->id) {
                    $temuan[] = ['tingkat' => 'kritis', 'kode' => 'R-04', 'pesan' => "{$saksi->nama} tercatat sebagai saksi sekaligus pihak/notaris."];

                    continue;
                }
                if ($h = $this->dilarangPasal40($saksi, $lain)) {
                    $temuan[] = ['tingkat' => 'kritis', 'kode' => 'R-04', 'pesan' => "Saksi {$saksi->nama} memiliki hubungan \"{$h}\" dengan {$lain->nama} (Pasal 40 UUJN)."];
                } elseif (($a = static::namaKeluarga($saksi->nama)) && $a === static::namaKeluarga($lain->nama)) {
                    $temuan[] = ['tingkat' => 'perlu_dicek', 'kode' => 'R-04', 'pesan' => "Saksi {$saksi->nama} dan {$lain->nama} memiliki nama belakang sama; pastikan tidak ada hubungan keluarga."];
                }
            }
        }

        return $temuan;
    }
}
