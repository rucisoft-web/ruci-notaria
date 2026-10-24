<?php

namespace App\Filament\Resources\LokasiArsip\Pages;

use App\Filament\Resources\LokasiArsip\LokasiArsipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageLokasiArsip extends ManageRecords
{
    protected static string $resource = LokasiArsipResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
