<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Template hanya dapat dipakai bila disetujui notaris. */
enum StatusTemplate: string implements HasColor, HasLabel
{
    use HasOptions;

    case Draf = 'draf';
    case Disetujui = 'disetujui';
    case Usang = 'usang';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draf => 'Draf',
            self::Disetujui => 'Disetujui',
            self::Usang => 'Usang',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Draf => 'gray',
            self::Disetujui => 'success',
            self::Usang => 'danger',
        };
    }
}
