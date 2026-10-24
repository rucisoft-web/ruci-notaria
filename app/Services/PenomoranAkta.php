<?php

namespace App\Services;

use App\Enums\StatusAkta;
use App\Enums\StatusBerkas;
use App\Models\Akta;
use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pemberian nomor akta saat penandatanganan dicatat.
 *
 * - Nomor bulanan dimulai dari 1 setiap bulan; nomor urut repertorium berlanjut sepanjang tahun.
 * - Penomoran dikunci per notaris (SELECT ... FOR UPDATE pada baris jabatan) sehingga dua pengguna
 *   yang mencatat bersamaan tidak menghasilkan nomor ganda atau celah.
 * - Nomor hanya diberikan untuk tanggal terakhir yang sudah bernomor atau sesudahnya, dan jam tidak boleh
 *   lebih awal dari akta bernomor sebelumnya pada tanggal yang sama, agar urutan nomor sesuai urutan waktu.
 */
class PenomoranAkta
{
    public function __construct(private KesiapanAkta $kesiapan, private KalkulatorTenggat $tenggat) {}

    /**
     * @param  array{dibacakan?: bool, renvoi_tambah?: int, renvoi_coret?: int, renvoi_ganti?: int, catatan_penandatanganan?: string|null, alasan_kesampingkan?: string|null}  $hasil
     */
    public function beriNomor(Akta $akta, Carbon $tanggal, string $jam, User $oleh, array $hasil = []): Akta
    {
        if (! $oleh->role?->isNotaris()) {
            throw ValidationException::withMessages(['akta' => 'Hanya notaris yang dapat memberi nomor akta.']);
        }
        if ($akta->isBernomor()) {
            throw ValidationException::withMessages(['akta' => 'Akta sudah bernomor.']);
        }
        if ($akta->status === StatusAkta::Batal) {
            throw ValidationException::withMessages(['akta' => 'Akta sudah dibatalkan.']);
        }
        if (! preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $jam)) {
            throw ValidationException::withMessages(['jam' => 'Format jam HH:MM.']);
        }
        if ($tanggal->isFuture() && ! $tanggal->isToday()) {
            throw ValidationException::withMessages(['tanggal' => 'Tanggal akta tidak boleh di masa depan.']);
        }

        $temuan = $this->kesiapan->periksa($akta, $tanggal);
        if (KesiapanAkta::adaKritis($temuan) && blank($hasil['alasan_kesampingkan'] ?? null)) {
            throw ValidationException::withMessages(['akta' => 'Masih ada temuan kritis: '.collect($temuan)->where('tingkat', 'kritis')->pluck('pesan')->implode(' ')]);
        }

        return DB::transaction(function () use ($akta, $tanggal, $jam, $oleh, $hasil, $temuan) {
            Jabatan::query()->whereKey($akta->jabatan_id)->lockForUpdate()->first();
            $akta->refresh();

            if ($akta->isBernomor()) {
                throw ValidationException::withMessages(['akta' => 'Akta sudah bernomor oleh pengguna lain.']);
            }

            $terakhir = Akta::query()->where('jabatan_id', $akta->jabatan_id)->bernomor()
                ->orderByDesc('tanggal')->orderByDesc('nomor_urut')->first(['tanggal', 'jam', 'nomor_bulanan']);

            if ($terakhir && $tanggal->toDateString() < $terakhir->tanggal->toDateString()) {
                throw ValidationException::withMessages(['tanggal' => 'Sudah ada akta bernomor bertanggal '.$terakhir->tanggal->format('d/m/Y').'; akta baru tidak boleh bertanggal lebih awal.']);
            }
            if ($terakhir && $tanggal->isSameDay($terakhir->tanggal) && $jam < substr((string) $terakhir->jam, 0, 5)) {
                throw ValidationException::withMessages(['jam' => 'Jam akta tidak boleh lebih awal dari akta No. '.$terakhir->nomor_bulanan.' ('.substr((string) $terakhir->jam, 0, 5).') pada tanggal yang sama.']);
            }

            $tahun = $tanggal->year;
            $bulan = $tanggal->month;
            $nomorBulanan = (int) Akta::query()->where('jabatan_id', $akta->jabatan_id)->where('tahun', $tahun)->where('bulan', $bulan)->max('nomor_bulanan') + 1;
            $nomorUrut = (int) Akta::query()->where('jabatan_id', $akta->jabatan_id)->where('tahun', $tahun)->max('nomor_urut') + 1;

            $catatan = trim(($hasil['catatan_penandatanganan'] ?? '').(filled($hasil['alasan_kesampingkan'] ?? null) && KesiapanAkta::adaKritis($temuan)
                ? "\nTemuan kritis dikesampingkan notaris: ".$hasil['alasan_kesampingkan'] : ''));

            $akta->forceFill([
                'tahun' => $tahun,
                'bulan' => $bulan,
                'nomor_bulanan' => $nomorBulanan,
                'nomor_urut' => $nomorUrut,
                'tanggal' => $tanggal->toDateString(),
                'jam' => $jam.':00',
                'dinomori_oleh' => $oleh->id,
                'dinomori_at' => now(),
                'status' => StatusAkta::Ditandatangani,
                'dibacakan' => $hasil['dibacakan'] ?? true,
                'renvoi_tambah' => $hasil['renvoi_tambah'] ?? 0,
                'renvoi_coret' => $hasil['renvoi_coret'] ?? 0,
                'renvoi_ganti' => $hasil['renvoi_ganti'] ?? 0,
                'catatan_penandatanganan' => $catatan ?: null,
            ])->save();

            // Potret data penghadap dan saksi dibekukan pada saat akta ditandatangani.
            foreach ($akta->penghadap()->with('subjek')->get() as $p) {
                $p->update(['potret' => $p->subjek->potret()]);
            }
            foreach ($akta->saksi()->with('subjek')->get() as $s) {
                $s->update(['potret' => $s->subjek->potret()]);
            }

            $this->tenggat->buatUntukAkta($akta);

            $berkas = $akta->berkas;
            if (in_array($berkas->status, [StatusBerkas::Baru, StatusBerkas::Dokumen, StatusBerkas::Draf, StatusBerkas::Persetujuan, StatusBerkas::Dijadwalkan], true)) {
                $berkas->update(['status' => StatusBerkas::Ditandatangani]);
            }

            return $akta;
        });
    }
}
