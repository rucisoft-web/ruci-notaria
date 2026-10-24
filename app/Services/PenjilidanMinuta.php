<?php

namespace App\Services;

use App\Models\Akta;
use App\Models\JilidMinuta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Membagi minuta satu bulan menjadi buku jilid berisi paling banyak 50 akta (Pasal 16 ayat 1 huruf g UUJN). */
class PenjilidanMinuta
{
    public const MAKS_PER_JILID = 50;

    /** @return list<JilidMinuta> */
    public function susun(int $jabatanId, int $tahun, int $bulan, ?int $lokasiId = null): array
    {
        return DB::transaction(function () use ($jabatanId, $tahun, $bulan, $lokasiId) {
            $ada = JilidMinuta::query()->where(compact('tahun', 'bulan'))->where('jabatan_id', $jabatanId);
            if ((clone $ada)->whereNotNull('dijilid_at')->exists()) {
                throw ValidationException::withMessages(['jilid' => 'Minuta bulan ini sudah dijilid; susunan tidak dapat diubah.']);
            }

            $akta = Akta::query()->periode($jabatanId, $tahun, $bulan)->bernomor()->where('sifat', 'minuta')->orderBy('nomor_bulanan')->get();
            if ($akta->isEmpty()) {
                throw ValidationException::withMessages(['jilid' => 'Tidak ada minuta pada bulan tersebut.']);
            }

            Akta::query()->whereIn('jilid_minuta_id', (clone $ada)->pluck('id'))->update(['jilid_minuta_id' => null]);
            (clone $ada)->delete();

            $hasil = [];
            foreach ($akta->chunk(self::MAKS_PER_JILID)->values() as $i => $isi) {
                $jilid = JilidMinuta::query()->create([
                    'jabatan_id' => $jabatanId, 'tahun' => $tahun, 'bulan' => $bulan, 'nomor_jilid' => $i + 1,
                    'nomor_awal' => $isi->first()->nomor_bulanan, 'nomor_akhir' => $isi->last()->nomor_bulanan,
                    'jumlah_minuta' => $isi->count(), 'lokasi_arsip_id' => $lokasiId,
                ]);
                Akta::query()->whereIn('id', $isi->pluck('id'))->update(['jilid_minuta_id' => $jilid->id]);
                $hasil[] = $jilid;
            }

            return $hasil;
        });
    }
}
