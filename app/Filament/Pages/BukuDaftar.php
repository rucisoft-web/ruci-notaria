<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\WithRole;
use App\Models\Jabatan;
use App\Services\BukuDaftar as Penyusun;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Buku daftar wajib (Pasal 58 sampai 59 UUJN) dihasilkan dari register, ditampilkan dan dicetak per bulan. */
class BukuDaftar extends Page
{
    use WithRole;

    public const BULAN = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    protected static ?string $title = 'Buku Daftar';

    protected static ?string $slug = 'buku-daftar';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Buku Daftar & Protokol';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.buku-daftar';

    public ?int $jabatanId = null;

    public int $tahun;

    public int $bulan;

    public string $jenis = 'repertorium';

    public static function canAccess(): bool
    {
        return static::canViewFiles();
    }

    public function mount(): void
    {
        $this->jabatanId = Jabatan::utama()?->id;
        $this->tahun = now()->year;
        $this->bulan = now()->month;
    }

    protected function getViewData(): array
    {
        $jabatan = $this->jabatanId ? Jabatan::find($this->jabatanId) : null;
        $params = ['jabatan' => $this->jabatanId, 'tahun' => $this->tahun, 'bulan' => $this->bulan];

        return [
            'jabatanOptions' => Jabatan::options(),
            'jabatan' => $jabatan,
            'isi' => $jabatan && isset(Penyusun::JENIS[$this->jenis]) ? app(Penyusun::class)->isi($this->jenis, $jabatan->id, $this->tahun, $this->bulan) : collect(),
            'urlCetak' => $jabatan ? route('cetak.buku', $params + ['jenis' => $this->jenis]) : null,
            'urlMpd' => $jabatan ? route('cetak.laporan-mpd', $params) : null,
        ];
    }
}
