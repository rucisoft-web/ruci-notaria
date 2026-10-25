<?php

namespace App\Filament\Pages\Panduan;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Halaman bacaan di grup Panduan. Isinya sama dengan halaman publik yang dapat dibuka tanpa login. */
abstract class HalamanPanduan extends Page
{
    protected static string|UnitEnum|null $navigationGroup = 'Panduan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected string $view = 'filament.pages.panduan';

    /** Nama view isi di resources/views/panduan dan nama rute publiknya. */
    abstract protected static function isi(): string;

    abstract protected static function rutePublik(): string;

    protected function getViewData(): array
    {
        return ['isi' => 'panduan.'.static::isi(), 'publik' => false, 'urlPublik' => route(static::rutePublik())];
    }
}
