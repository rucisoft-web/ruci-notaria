<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status tagihan. */
enum StatusTagihan: string implements HasColor, HasLabel
{
    use HasOptions;

    case Draf = 'draf';
    case Terbit = 'terbit';
    case Lunas = 'lunas';
    case Batal = 'batal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Terbit => 'Terbit',
            self::Lunas => 'Lunas',
            self::Batal => 'Batal',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Terbit => 'warning',
            self::Lunas => 'success',
            self::Batal => 'danger',
        };
    }
}
