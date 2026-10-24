<?php

namespace App\Services;

use App\Enums\JenisSurat;
use App\Models\Jabatan;
use App\Models\SuratBawahTangan;
use Illuminate\Support\Facades\DB;

/** Nomor legalisasi, waarmerking, dan pengesahan fotokopi: berurutan per notaris, per jenis, per tahun. */
class PenomoranSurat
{
    public function catat(array $data): SuratBawahTangan
    {
        return DB::transaction(function () use ($data) {
            $jabatan = Jabatan::query()->whereKey($data['jabatan_id'])->lockForUpdate()->firstOrFail();
            $jenis = $data['jenis'] instanceof JenisSurat ? $data['jenis'] : JenisSurat::from($data['jenis']);
            $tahun = (int) date('Y', strtotime((string) $data['tanggal']));
            $urut = (int) SuratBawahTangan::query()->where('jabatan_id', $jabatan->id)->where('jenis', $jenis->value)->where('tahun', $tahun)->max('nomor_urut') + 1;

            $surat = new SuratBawahTangan($data);
            $surat->forceFill([
                'tahun' => $tahun,
                'nomor_urut' => $urut,
                'nomor' => sprintf('%d/%s/%s/%d', $urut, $jenis->singkatan(), $jabatan->kode, $tahun),
                'created_by' => auth()->id(),
            ])->save();

            return $surat;
        });
    }
}
