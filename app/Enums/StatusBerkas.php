<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Tahap berkas (order klien). */
enum StatusBerkas: string implements HasColor, HasLabel
{
    use HasOptions;

    case Baru = 'baru';
    case Dokumen = 'dokumen';
    case Draf = 'draf';
    case Persetujuan = 'persetujuan';
    case Dijadwalkan = 'dijadwalkan';
    case Ditandatangani = 'ditandatangani';
    case PascaAkta = 'pasca_akta';
    case Selesai = 'selesai';
    case Batal = 'batal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Baru => 'Baru',
            self::Dokumen => 'Pengumpulan dokumen',
            self::Draf => 'Penyusunan draf',
            self::Persetujuan => 'Menunggu persetujuan draf',
            self::Dijadwalkan => 'Dijadwalkan tanda tangan',
            self::Ditandatangani => 'Ditandatangani',
            self::PascaAkta => 'Pascaakta (AHU/BPN/salinan)',
            self::Selesai => 'Selesai',
            self::Batal => 'Batal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Baru => 'gray',
            self::Dokumen => 'warning',
            self::Draf => 'info',
            self::Persetujuan => 'info',
            self::Dijadwalkan => 'primary',
            self::Ditandatangani => 'success',
            self::PascaAkta => 'warning',
            self::Selesai => 'success',
            self::Batal => 'danger',
        };
    }

    /** Status yang dianggap masih berjalan. */
    public static function aktif(): array
    {
        return [self::Baru, self::Dokumen, self::Draf, self::Persetujuan, self::Dijadwalkan, self::Ditandatangani, self::PascaAkta];
    }
}
