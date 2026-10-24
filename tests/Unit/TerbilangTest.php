<?php

namespace Tests\Unit;

use App\Support\Terbilang;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TerbilangTest extends TestCase
{
    public static function angka(): array
    {
        return [
            [0, 'nol'], [1, 'satu'], [11, 'sebelas'], [12, 'dua belas'], [20, 'dua puluh'], [100, 'seratus'], [111, 'seratus sebelas'],
            [1000, 'seribu'], [1001, 'seribu satu'], [2026, 'dua ribu dua puluh enam'], [1_250_000, 'satu juta dua ratus lima puluh ribu'],
            [1_000_000_000, 'satu miliar'], [3_000_000_000_000, 'tiga triliun'], [-5, 'minus lima'],
        ];
    }

    #[DataProvider('angka')]
    public function test_angka(int $n, string $huruf): void
    {
        $this->assertSame($huruf, Terbilang::angka($n));
    }

    public function test_rupiah_desimal_tanggal_dan_jam(): void
    {
        $this->assertSame('Rp1.250.000,00 (satu juta dua ratus lima puluh ribu Rupiah)', Terbilang::rupiah(1_250_000));
        $this->assertSame('sepuluh Rupiah lima puluh sen', Terbilang::rupiah(10.5, false));
        $this->assertSame('dua belas koma lima', Terbilang::desimal(12.5));
        $this->assertSame('hari Sabtu, tanggal dua puluh empat Oktober dua ribu dua puluh enam (24-10-2026)', Terbilang::hariTanggal('2026-10-24'));
        $this->assertSame('Pukul 10.30 WIB (sepuluh lewat tiga puluh menit Waktu Indonesia Barat)', Terbilang::jam('10:30'));
        $this->assertSame('Pukul 09.00 WITA (sembilan Waktu Indonesia Tengah)', Terbilang::jam('09:00', 'WITA'));
    }
}
