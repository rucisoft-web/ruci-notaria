<?php

namespace App\Services;

use App\Models\Akta;
use App\Models\DrafAkta;
use App\Support\Docx;
use App\Support\Terbilang;
use Illuminate\Support\Facades\Storage;

/**
 * Pemeriksa draf deterministik (lampiran B spesifikasi): variabel tersisa, kecocokan nama/NIK dengan profil,
 * kecocokan angka dengan terbilang, ditambah pemeriksaan kesiapan dan hubungan keluarga.
 */
class PemeriksaDraf
{
    public function __construct(private KesiapanAkta $kesiapan) {}

    public static function normal(string $s): string
    {
        return preg_replace('/[^a-z0-9]+/', '', mb_strtolower($s));
    }

    /** @return list<array{tingkat: string, kode: string, pesan: string}> */
    public function periksaTeks(string $teks, Akta $akta): array
    {
        $t = [];
        $teksNormal = static::normal($teks);

        if (preg_match_all('/\$\{([^}]+)\}/', $teks, $m)) {
            $t[] = ['tingkat' => 'kritis', 'kode' => 'R-01', 'pesan' => 'Variabel belum terisi: '.implode(', ', array_unique($m[1])).'.'];
        }
        if (str_contains($teks, '[') && preg_match('/\[[^\]]{3,}\]/u', $teks, $m)) {
            $t[] = ['tingkat' => 'kritis', 'kode' => 'R-01', 'pesan' => 'Masih ada teks petunjuk dalam kurung siku: '.mb_substr($m[0], 0, 80).'.'];
        }
        if (str_contains($teks, '…………')) {
            $t[] = ['tingkat' => 'perlu_dicek', 'kode' => 'R-01', 'pesan' => 'Masih ada titik-titik (nomor/tanggal/jam belum ditetapkan). Buat ulang draf setelah akta bernomor.'];
        }

        foreach ($akta->penghadap()->with('subjek')->get() as $p) {
            $d = $p->data();
            if (filled($d['nama'] ?? null) && ! str_contains($teksNormal, static::normal($d['nama']))) {
                $t[] = ['tingkat' => 'kritis', 'kode' => 'R-06', 'pesan' => "Nama penghadap \"{$d['nama']}\" tidak ditemukan dalam draf."];
            }
            if (filled($d['nik'] ?? null) && ! str_contains($teksNormal, static::normal($d['nik']))) {
                $t[] = ['tingkat' => 'kritis', 'kode' => 'R-06', 'pesan' => "Nomor identitas {$d['nama']} tidak ditemukan atau berbeda dalam draf."];
            }
        }

        // Rp1.250.000,00 (satu juta dua ratus lima puluh ribu Rupiah)
        if (preg_match_all('/Rp\.?\s?([\d.]+)(?:,(\d{1,2}))?\s*,?-?\s*\(([^)]+)\)/u', $teks, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $angka = (float) (str_replace('.', '', $x[1]).'.'.($x[2] ?? '0'));
                if (static::normal($x[3]) !== static::normal(Terbilang::rupiah($angka, false))) {
                    $t[] = ['tingkat' => 'kritis', 'kode' => 'R-07', 'pesan' => "Terbilang tidak cocok: {$x[0]} — seharusnya \"".Terbilang::rupiah($angka, false).'".'];
                }
            }
        }
        // 12 (dua belas) atau dua belas (12)
        if (preg_match_all('/\b(\d{1,9})\s+\(([a-z ]{3,})\)/u', $teks, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                if (static::normal($x[2]) !== static::normal(Terbilang::angka((int) $x[1]))) {
                    $t[] = ['tingkat' => 'kritis', 'kode' => 'R-07', 'pesan' => "Terbilang tidak cocok: {$x[0]} — seharusnya \"".Terbilang::angka((int) $x[1]).'".'];
                }
            }
        }
        if (preg_match_all('/\b([a-z][a-z ]{2,}[a-z])\s+\((\d{1,9})\)/u', $teks, $m, PREG_SET_ORDER)) {
            foreach ($m as $x) {
                $huruf = Terbilang::angka((int) $x[2]);
                // Bandingkan hanya bagian akhir frasa sepanjang terbilang yang benar.
                if (! str_ends_with(static::normal($x[1]), static::normal($huruf)) && $this->tampakTerbilang($x[1])) {
                    $t[] = ['tingkat' => 'kritis', 'kode' => 'R-07', 'pesan' => "Terbilang tidak cocok: \"{$x[0]}\" — seharusnya \"{$huruf} ({$x[2]})\"."];
                }
            }
        }

        return $t;
    }

    private function tampakTerbilang(string $frasa): bool
    {
        $kata = preg_split('/\s+/', trim(mb_strtolower($frasa)));

        return in_array(end($kata), ['satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas', 'belas', 'puluh', 'ratus', 'ribu', 'juta', 'miliar', 'seratus', 'seribu'], true);
    }

    public function periksa(DrafAkta $draf): DrafAkta
    {
        $teks = Docx::teks(Storage::disk('local')->path($draf->path));
        $akta = $draf->akta;
        $kesiapan = array_filter($this->kesiapan->periksa($akta), fn ($x) => ! in_array($x['kode'], ['R-01', 'R-17'], true));
        $temuan = array_values(array_merge($this->periksaTeks($teks, $akta), $kesiapan));
        $draf->update(['temuan' => $temuan, 'diperiksa_at' => now()]);

        return $draf;
    }
}
