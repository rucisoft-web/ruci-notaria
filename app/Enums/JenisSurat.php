<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Layanan atas surat di bawah tangan (Pasal 15 ayat 2 UUJN). */
enum JenisSurat: string implements HasColor, HasLabel
{
    use HasOptions;

    case Legalisasi = 'legalisasi';
    case Waarmerking = 'waarmerking';
    case Fotokopi = 'fotokopi';

    public function getLabel(): string
    {
        return match ($this) {
            self::Legalisasi => 'Legalisasi (disahkan)',
            self::Waarmerking => 'Waarmerking (dibukukan)',
            self::Fotokopi => 'Pengesahan fotokopi',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Legalisasi => 'primary',
            self::Waarmerking => 'info',
            self::Fotokopi => 'gray',
        };
    }

    /** Singkatan pada nomor, misal 12/Leg/2026. */
    public function singkatan(): string
    {
        return match ($this) {
            self::Legalisasi => 'Leg',
            self::Waarmerking => 'W',
            self::Fotokopi => 'FK',
        };
    }
}
