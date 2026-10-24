<?php

namespace App\Filament\Resources\Tagihan\Pages;

use App\Filament\Resources\Tagihan\TagihanResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/** Tagihan dalam mode baca (tamu situs demo dan peran tanpa hak keuangan). */
class ViewTagihan extends ViewRecord
{
    protected static string $resource = TagihanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak')->label('Cetak')->icon(Heroicon::OutlinedPrinter)->color('gray')
                ->url(route('cetak.tagihan', $this->getRecord()))->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return 'Tagihan '.$this->getRecord()->nomor;
    }
}
