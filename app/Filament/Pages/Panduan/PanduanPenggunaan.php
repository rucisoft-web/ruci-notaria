<?php

namespace App\Filament\Pages\Panduan;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PanduanPenggunaan extends HalamanPanduan
{
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static ?string $navigationLabel = 'Panduan penggunaan';

    protected static ?string $title = 'Panduan penggunaan Ruci Notaria';

    protected static ?string $slug = 'panduan/penggunaan';

    protected static function isi(): string
    {
        return 'penggunaan';
    }

    protected static function rutePublik(): string
    {
        return 'panduan.penggunaan';
    }
}
