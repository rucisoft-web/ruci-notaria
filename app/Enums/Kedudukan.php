<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Kedudukan penghadap dalam akta. */
enum Kedudukan: string implements HasColor, HasLabel
{
    use HasOptions;

    case DiriSendiri = 'diri_sendiri';
    case Kuasa = 'kuasa';
    case MewakiliBadan = 'mewakili_badan';

    public function getLabel(): string
    {
        return match ($this) {
            self::DiriSendiri => 'Untuk diri sendiri',
            self::Kuasa => 'Selaku kuasa',
            self::MewakiliBadan => 'Mewakili badan',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DiriSendiri => 'gray',
            self::Kuasa => 'info',
            self::MewakiliBadan => 'primary',
        };
    }
}
