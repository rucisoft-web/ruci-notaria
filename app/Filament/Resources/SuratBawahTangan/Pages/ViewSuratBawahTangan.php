<?php

namespace App\Filament\Resources\SuratBawahTangan\Pages;

use App\Filament\Resources\SuratBawahTangan\SuratBawahTanganResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSuratBawahTangan extends ViewRecord
{
    protected static string $resource = SuratBawahTanganResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cetak')->label('Cetak pengesahan')->icon(Heroicon::OutlinedPrinter)->color('gray')
                ->url(route('cetak.pengesahan', $this->getRecord()))->openUrlInNewTab(),
        ];
    }

    public function getTitle(): string
    {
        return $this->getRecord()->nomor;
    }
}
