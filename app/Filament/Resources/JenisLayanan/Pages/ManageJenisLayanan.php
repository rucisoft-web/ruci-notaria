<?php

namespace App\Filament\Resources\JenisLayanan\Pages;

use App\Filament\Resources\JenisLayanan\JenisLayananResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageJenisLayanan extends ManageRecords
{
    protected static string $resource = JenisLayananResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
