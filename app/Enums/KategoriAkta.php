<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Kategori akta yang menentukan buku daftar khusus dan aturan saksi. */
enum KategoriAkta: string implements HasColor, HasLabel
{
    use HasOptions;

    case Umum = 'umum';
    case Wasiat = 'wasiat';
    case WasiatBawahTangan = 'wasiat_bawah_tangan';
    case Protes = 'protes';
    case PengakuanUtang = 'pengakuan_utang';

    public function getLabel(): string
    {
        return match ($this) {
            self::Umum => 'Umum',
            self::Wasiat => 'Wasiat',
            self::WasiatBawahTangan => 'Penyimpanan wasiat di bawah tangan',
            self::Protes => 'Protes surat berharga',
            self::PengakuanUtang => 'Pengakuan utang (dapat grosse)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Umum => 'gray',
            self::Wasiat => 'warning',
            self::WasiatBawahTangan => 'warning',
            self::Protes => 'danger',
            self::PengakuanUtang => 'info',
        };
    }

    /** Jumlah saksi akta minimum (Pasal 16 ayat 1 huruf m UUJN). */
    public function minimumSaksi(): int
    {
        return $this === self::WasiatBawahTangan ? 4 : 2;
    }

    public function masukDaftarWasiat(): bool
    {
        return in_array($this, [self::Wasiat, self::WasiatBawahTangan], true);
    }
}
