<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Buku daftar wajib (Pasal 58 sampai 59 UUJN). */
enum JenisDaftar: string implements HasColor, HasLabel
{
    use HasOptions;

    case Repertorium = 'repertorium';
    case Klapper = 'klapper';
    case Legalisasi = 'legalisasi';
    case Waarmerking = 'waarmerking';
    case Protes = 'protes';
    case Wasiat = 'wasiat';

    public function getLabel(): string
    {
        return match ($this) {
            self::Repertorium => 'Daftar akta (repertorium)',
            self::Klapper => 'Daftar nama penghadap (klapper)',
            self::Legalisasi => 'Daftar surat di bawah tangan yang disahkan',
            self::Waarmerking => 'Daftar surat di bawah tangan yang dibukukan',
            self::Protes => 'Daftar protes',
            self::Wasiat => 'Daftar wasiat',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Repertorium => 'primary',
            self::Klapper => 'info',
            self::Legalisasi => 'info',
            self::Waarmerking => 'info',
            self::Protes => 'gray',
            self::Wasiat => 'warning',
        };
    }
}
