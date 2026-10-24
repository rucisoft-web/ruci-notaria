<?php

namespace App\Services;

use App\Enums\StatusAkta;
use App\Enums\StatusTagihan;
use App\Models\Berkas;
use App\Models\TagihanItem;
use App\Support\Money;

/**
 * Batas honorarium notaris menurut Pasal 36 UUJN, dihitung dari nilai ekonomis objek setiap akta:
 * s.d. Rp100 juta paling besar 2,5%; di atas Rp100 juta s.d. Rp1 miliar paling besar 1,5%; di atas Rp1 miliar paling besar 1%.
 * Tanpa nilai ekonomis (nilai sosiologis) paling besar Rp5 juta.
 */
class Honorarium
{
    public const MAKS_SOSIOLOGIS = 5_000_000;

    public static function batas(?float $nilaiEkonomis): float
    {
        if ($nilaiEkonomis === null || $nilaiEkonomis <= 0) {
            return self::MAKS_SOSIOLOGIS;
        }

        $persen = match (true) {
            $nilaiEkonomis <= 100_000_000 => 0.025,
            $nilaiEkonomis <= 1_000_000_000 => 0.015,
            default => 0.01,
        };

        return round($nilaiEkonomis * $persen, 2);
    }

    /** Batas honorarium PPAT: 1% dari harga transaksi (PP 24/2016). */
    public static function batasPpat(float $hargaTransaksi): float
    {
        return round($hargaTransaksi * 0.01, 2);
    }

    /** Batas honorarium satu berkas: jumlah batas setiap akta yang tidak batal; berkas cuma-cuma (Pasal 37) bernilai nol. */
    public static function batasBerkas(Berkas $berkas): float
    {
        if ($berkas->cuma_cuma) {
            return 0;
        }
        $akta = $berkas->akta()->where('status', '!=', StatusAkta::Batal)->get(['nilai_ekonomis']);

        return $akta->isEmpty() ? self::batas(null) : (float) $akta->sum(fn ($a) => self::batas($a->nilai_ekonomis ? (float) $a->nilai_ekonomis : null));
    }

    /** Honorarium yang sudah ditagihkan pada berkas di luar tagihan tertentu (tagihan batal tidak dihitung). */
    public static function sudahDitagih(Berkas $berkas, ?int $kecualiTagihanId = null): float
    {
        return (float) TagihanItem::query()->where('jenis', 'honorarium')
            ->whereHas('tagihan', fn ($q) => $q->where('berkas_id', $berkas->id)->where('status', '!=', StatusTagihan::Batal)
                ->when($kecualiTagihanId, fn ($q) => $q->whereKeyNot($kecualiTagihanId)))
            ->sum('jumlah');
    }

    /** Pesan galat bila honorarium baru melampaui batas, atau null bila aman. */
    public static function periksa(Berkas $berkas, float $honorariumBaru, ?int $kecualiTagihanId = null): ?string
    {
        $batas = self::batasBerkas($berkas);
        $total = self::sudahDitagih($berkas, $kecualiTagihanId) + $honorariumBaru;

        if ($total <= $batas + 0.005) {
            return null;
        }

        return $berkas->cuma_cuma
            ? 'Berkas ini jasa hukum cuma-cuma (Pasal 37 UUJN); honorarium tidak dapat ditagihkan.'
            : 'Total honorarium berkas '.Money::format($total).' melampaui batas Pasal 36 UUJN sebesar '.Money::format($batas).'.';
    }
}
