<?php

namespace App\Services;

use App\Models\Akta;
use App\Models\HariLibur;
use App\Models\Jabatan;
use App\Models\Pengaturan;
use App\Models\Tenggat;
use App\Support\Terbilang;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** Menghitung dan membuat tenggat hukum. Tenggat dibuat idempoten lewat kolom `kunci`. */
class KalkulatorTenggat
{
    /** @var array<string, true>|null */
    private ?array $libur = null;

    public function isHariKerja(CarbonInterface $tanggal): bool
    {
        $this->libur ??= HariLibur::query()->pluck('tanggal')->mapWithKeys(fn ($d) => [Carbon::parse($d)->toDateString() => true])->all();

        return ! $tanggal->isWeekend() && ! isset($this->libur[$tanggal->toDateString()]);
    }

    /** Hari kerja ke-n setelah tanggal (tanggal itu sendiri tidak dihitung). */
    public function tambahHariKerja(CarbonInterface $tanggal, int $hari): Carbon
    {
        $t = Carbon::parse($tanggal)->startOfDay();

        while ($hari > 0) {
            $t->addDay();
            if ($this->isHariKerja($t)) {
                $hari--;
            }
        }

        return $t;
    }

    public function hitung(CarbonInterface $basis, int $hari, bool $hariKerja): Carbon
    {
        return $hariKerja ? $this->tambahHariKerja($basis, $hari) : Carbon::parse($basis)->startOfDay()->addDays($hari);
    }

    /** Tenggat yang dipicu akta bernomor, sesuai aturan pada jenis layanannya. */
    public function buatUntukAkta(Akta $akta): void
    {
        $kode = (array) ($akta->jenisLayanan?->aturan_tenggat ?? $akta->berkas->jenisLayanan?->aturan_tenggat ?? []);
        $aturan = config('notaria.tenggat_akta');

        foreach ($kode as $k) {
            if (! isset($aturan[$k])) {
                continue;
            }
            $a = $aturan[$k];
            Tenggat::query()->firstOrCreate(['kunci' => "{$k}:akta:{$akta->id}"], [
                'kode' => $k,
                'judul' => $a['judul'].' — '.$akta->judul.' No. '.$akta->nomor_bulanan,
                'jatuh_tempo' => $this->hitung($akta->tanggal, $a['hari'], $a['hari_kerja']),
                'jabatan_id' => $akta->jabatan_id,
                'berkas_id' => $akta->berkas_id,
                'akta_id' => $akta->id,
            ]);
        }
    }

    /** Tenggat bulanan (MPD, wasiat, jilid) untuk laporan atas bulan $periode, jatuh tempo di bulan berikutnya. */
    public function buatBulanan(CarbonInterface $periode): int
    {
        $bulan = Carbon::parse($periode)->startOfMonth();
        $berikut = $bulan->copy()->addMonth();
        $label = Terbilang::namaBulan($bulan->month).' '.$bulan->year;
        $hariOverride = ['T-WST' => (int) Pengaturan::get('tenggat.wasiat_hari', 5), 'T-JLD' => (int) Pengaturan::get('tenggat.jilid_hari', 10)];
        $dibuat = 0;

        foreach (Jabatan::query()->where('is_active', true)->get() as $jabatan) {
            foreach (config('notaria.tenggat_bulanan') as $kode => $a) {
                $hari = $hariOverride[$kode] ?? $a['hari'];
                $tenggat = Tenggat::query()->firstOrCreate(['kunci' => "{$kode}:{$jabatan->id}:{$bulan->format('Y-m')}"], [
                    'kode' => $kode,
                    'judul' => $a['judul'].' ('.$label.')',
                    'jatuh_tempo' => $berikut->copy()->day(min($hari, $berikut->daysInMonth)),
                    'jabatan_id' => $jabatan->id,
                    'periode' => $bulan->format('Y-m'),
                ]);
                $dibuat += $tenggat->wasRecentlyCreated ? 1 : 0;
            }
        }

        return $dibuat;
    }
}
