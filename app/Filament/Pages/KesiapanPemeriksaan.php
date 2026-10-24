<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\WithRole;
use App\Models\Jabatan;
use App\Services\KesiapanPemeriksaan as Pemeriksa;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Daftar periksa sebelum pemeriksaan protokol oleh MPD (Pasal 70 UUJN). */
class KesiapanPemeriksaan extends Page
{
    use WithRole;

    protected static ?string $title = 'Kesiapan Pemeriksaan MPD';

    protected static ?string $navigationLabel = 'Kesiapan pemeriksaan';

    protected static ?string $slug = 'kesiapan-pemeriksaan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Buku Daftar & Protokol';

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.pages.kesiapan-pemeriksaan';

    public ?int $jabatanId = null;

    public int $tahun;

    public static function canAccess(): bool
    {
        return static::canViewFiles();
    }

    public function mount(): void
    {
        $this->jabatanId = Jabatan::utama()?->id;
        $this->tahun = now()->year;
    }

    protected function getViewData(): array
    {
        $p = app(Pemeriksa::class);

        return [
            'jabatanOptions' => Jabatan::options(),
            'hasil' => $this->jabatanId ? $p->periksa($this->jabatanId, $this->tahun) : [],
            'celah' => $this->jabatanId ? $p->celahNomor($this->jabatanId, $this->tahun) : [],
        ];
    }
}
