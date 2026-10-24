<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Jenis turunan akta. */
enum JenisSalinan: string implements HasColor, HasLabel
{
    use HasOptions;

    case Salinan = 'salinan';
    case Kutipan = 'kutipan';
    case Grosse = 'grosse';

    public function getLabel(): string
    {
        return match ($this) {
            self::Salinan => 'Salinan',
            self::Kutipan => 'Kutipan',
            self::Grosse => 'Grosse',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Salinan => 'primary',
            self::Kutipan => 'info',
            self::Grosse => 'warning',
        };
    }
}
