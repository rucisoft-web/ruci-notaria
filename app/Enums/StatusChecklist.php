<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Status dokumen pada daftar periksa berkas. */
enum StatusChecklist: string implements HasColor, HasLabel
{
    use HasOptions;

    case Diminta = 'diminta';
    case Diterima = 'diterima';
    case Diverifikasi = 'diverifikasi';
    case Ditolak = 'ditolak';
    case TidakPerlu = 'tidak_perlu';

    public function getLabel(): string
    {
        return match ($this) {
            self::Diminta => 'Diminta',
            self::Diterima => 'Diterima',
            self::Diverifikasi => 'Diverifikasi',
            self::Ditolak => 'Ditolak',
            self::TidakPerlu => 'Tidak perlu',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Diminta => 'gray',
            self::Diterima => 'info',
            self::Diverifikasi => 'success',
            self::Ditolak => 'danger',
            self::TidakPerlu => 'gray',
        };
    }
}
