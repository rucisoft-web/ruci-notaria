<?php

namespace App\Filament\Pages\Panduan;

use BackedEnum;
use Filament\Support\Icons\Heroicon;

class MengapaRuciNotaria extends HalamanPanduan
{
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?string $navigationLabel = 'Mengapa Ruci Notaria';

    protected static ?string $title = 'Mengapa notaris memerlukan Ruci Notaria';

    protected static ?string $slug = 'panduan/mengapa-ruci-notaria';

    protected static function isi(): string
    {
        return 'mengapa';
    }

    protected static function rutePublik(): string
    {
        return 'panduan.mengapa';
    }
}
