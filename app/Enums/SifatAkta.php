<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Minuta disimpan sebagai protokol; originali diberikan aslinya kepada pihak. */
enum SifatAkta: string implements HasColor, HasLabel
{
    use HasOptions;

    case Minuta = 'minuta';
    case Originali = 'originali';

    public function getLabel(): string
    {
        return match ($this) {
            self::Minuta => 'Minuta',
            self::Originali => 'Originali',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Minuta => 'primary',
            self::Originali => 'gray',
        };
    }
}
