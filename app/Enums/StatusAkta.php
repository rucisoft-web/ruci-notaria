<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status akta. Akta bernomor tidak dapat dihapus atau dibatalkan. */
enum StatusAkta: string implements HasColor, HasLabel
{
    use HasOptions;

    case Draf = 'draf';
    case Dijadwalkan = 'dijadwalkan';
    case Ditandatangani = 'ditandatangani';
    case Batal = 'batal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Dijadwalkan => 'Dijadwalkan',
            self::Ditandatangani => 'Ditandatangani & bernomor',
            self::Batal => 'Batal (sebelum bernomor)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Dijadwalkan => 'info',
            self::Ditandatangani => 'success',
            self::Batal => 'danger',
        };
    }
}
