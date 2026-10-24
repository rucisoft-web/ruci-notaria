<?php

namespace App\Filament\Resources\BukuFisik\Pages;

use App\Filament\Resources\BukuFisik\BukuFisikResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBukuFisik extends ManageRecords
{
    protected static string $resource = BukuFisikResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
