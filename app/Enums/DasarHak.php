<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Dasar hak penerima salinan (Pasal 54 UUJN). */
enum DasarHak: string implements HasColor, HasLabel
{
    use HasOptions;

    case Pihak = 'pihak';
    case AhliWaris = 'ahli_waris';
    case PenerimaHak = 'penerima_hak';
    case Pengadilan = 'pengadilan';
    case PenegakHukum = 'penegak_hukum';
    case TidakBerhak = 'tidak_berhak';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pihak => 'Pihak dalam akta',
            self::AhliWaris => 'Ahli waris pihak',
            self::PenerimaHak => 'Orang yang memperoleh hak',
            self::Pengadilan => 'Perintah pengadilan',
            self::PenegakHukum => 'Penegak hukum dengan persetujuan MKN',
            self::TidakBerhak => 'Tidak berhak (ditolak)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pihak => 'success',
            self::AhliWaris => 'success',
            self::PenerimaHak => 'success',
            self::Pengadilan => 'warning',
            self::PenegakHukum => 'warning',
            self::TidakBerhak => 'danger',
        };
    }
}
