<?php

namespace App\Filament\Resources\SuratBawahTangan\Pages;

use App\Enums\JenisSurat;
use App\Filament\Resources\SuratBawahTangan\SuratBawahTanganResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListSuratBawahTangan extends ListRecords
{
    protected static string $resource = SuratBawahTanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make('legalisasi')->label('Legalisasi baru')->url(SuratBawahTanganResource::getUrl('create', ['jenis' => 'legalisasi'])),
            CreateAction::make('waarmerking')->label('Waarmerking baru')->color('gray')->url(SuratBawahTanganResource::getUrl('create', ['jenis' => 'waarmerking'])),
            CreateAction::make('fotokopi')->label('Pengesahan fotokopi')->color('gray')->url(SuratBawahTanganResource::getUrl('create', ['jenis' => 'fotokopi'])),
        ];
    }

    public function getTabs(): array
    {
        $tabs = ['semua' => Tab::make('Semua')];
        foreach (JenisSurat::cases() as $j) {
            $tabs[$j->value] = Tab::make($j->getLabel())->modifyQueryUsing(fn (Builder $query) => $query->where('jenis', $j->value));
        }

        return $tabs;
    }
}
