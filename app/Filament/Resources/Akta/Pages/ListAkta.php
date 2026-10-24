<?php

namespace App\Filament\Resources\Akta\Pages;

use App\Filament\Resources\Akta\AktaResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListAkta extends ListRecords
{
    protected static string $resource = AktaResource::class;

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua'),
            'proses' => Tab::make('Belum bernomor')->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', ['draf', 'dijadwalkan'])),
            'bulan_ini' => Tab::make('Bernomor bulan ini')->modifyQueryUsing(fn (Builder $query) => $query->bernomor()->where('tahun', now()->year)->where('bulan', now()->month)),
        ];
    }
}
