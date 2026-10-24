<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Penulisan angka dengan huruf sebagaimana diwajibkan Pasal 42 UUJN:
 * angka, nominal rupiah, tanggal, hari, dan jam.
 */
class Terbilang
{
    private const SATUAN = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

    private const BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    private const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    public static function angka(int|string $n): string
    {
        $n = (int) $n;

        if ($n === 0) {
            return 'nol';
        }

        if ($n < 0) {
            return 'minus '.self::angka(-$n);
        }

        return trim(preg_replace('/\s+/', ' ', self::susun($n)));
    }

    private static function susun(int $n): string
    {
        return match (true) {
            $n < 12 => self::SATUAN[$n],
            $n < 20 => self::SATUAN[$n - 10].' belas',
            $n < 100 => self::SATUAN[intdiv($n, 10)].' puluh '.self::susun($n % 10),
            $n < 200 => 'seratus '.self::susun($n - 100),
            $n < 1000 => self::SATUAN[intdiv($n, 100)].' ratus '.self::susun($n % 100),
            $n < 2000 => 'seribu '.self::susun($n - 1000),
            $n < 1_000_000 => self::susun(intdiv($n, 1000)).' ribu '.self::susun($n % 1000),
            $n < 1_000_000_000 => self::susun(intdiv($n, 1_000_000)).' juta '.self::susun($n % 1_000_000),
            $n < 1_000_000_000_000 => self::susun(intdiv($n, 1_000_000_000)).' miliar '.self::susun($n % 1_000_000_000),
            default => self::susun(intdiv($n, 1_000_000_000_000)).' triliun '.self::susun($n % 1_000_000_000_000),
        };
    }

    /** Desimal dibaca per angka: 12,5 → "dua belas koma lima". */
    public static function desimal(float|string $n, int $digits = 2): string
    {
        $text = rtrim(rtrim(number_format((float) $n, $digits, '.', ''), '0'), '.');
        [$bulat, $pecahan] = array_pad(explode('.', $text), 2, null);
        $hasil = self::angka((int) $bulat);

        if ($pecahan !== null && $pecahan !== '') {
            $hasil .= ' koma '.implode(' ', array_map(fn ($d) => $d === '0' ? 'nol' : self::SATUAN[(int) $d], str_split($pecahan)));
        }

        return $hasil;
    }

    /** "Rp1.250.000,00 (satu juta dua ratus lima puluh ribu Rupiah)". */
    public static function rupiah(float|int|string $n, bool $denganAngka = true): string
    {
        $n = round((float) $n, 2);
        $sen = (int) round(($n - floor($n)) * 100);
        $huruf = self::angka((int) floor($n)).' Rupiah'.($sen > 0 ? ' '.self::angka($sen).' sen' : '');

        return $denganAngka ? 'Rp'.number_format($n, 2, ',', '.').' ('.$huruf.')' : $huruf;
    }

    public static function namaBulan(int $bulan): string
    {
        return self::BULAN[$bulan];
    }

    public static function hari(CarbonInterface|string $tanggal): string
    {
        return self::HARI[Carbon::parse($tanggal)->dayOfWeek];
    }

    /** "dua puluh empat Oktober dua ribu dua puluh enam". */
    public static function tanggal(CarbonInterface|string $tanggal): string
    {
        $t = Carbon::parse($tanggal);

        return self::angka($t->day).' '.self::BULAN[$t->month].' '.self::angka($t->year);
    }

    /** "24-10-2026" sebagaimana ditulis dalam kurung pada akta. */
    public static function tanggalAngka(CarbonInterface|string $tanggal): string
    {
        return Carbon::parse($tanggal)->format('d-m-Y');
    }

    /** Format awal akta: "hari Sabtu, tanggal dua puluh empat Oktober dua ribu dua puluh enam (24-10-2026)". */
    public static function hariTanggal(CarbonInterface|string $tanggal): string
    {
        return 'hari '.self::hari($tanggal).', tanggal '.self::tanggal($tanggal).' ('.self::tanggalAngka($tanggal).')';
    }

    /** "Pukul 10.30 WIB (sepuluh lewat tiga puluh menit Waktu Indonesia Barat)". */
    public static function jam(string $jam, string $zona = 'WIB'): string
    {
        [$h, $m] = array_map('intval', explode(':', $jam) + [1 => 0]);
        $zonaPanjang = ['WIB' => 'Waktu Indonesia Barat', 'WITA' => 'Waktu Indonesia Tengah', 'WIT' => 'Waktu Indonesia Timur'][$zona] ?? $zona;
        $huruf = self::angka($h).($m > 0 ? ' lewat '.self::angka($m).' menit' : '');

        return sprintf('Pukul %02d.%02d %s (%s %s)', $h, $m, $zona, $huruf, $zonaPanjang);
    }
}
