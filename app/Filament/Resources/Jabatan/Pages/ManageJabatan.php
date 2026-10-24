<?php

namespace App\Filament\Resources\Jabatan\Pages;

use App\Filament\Resources\Jabatan\JabatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageJabatan extends ManageRecords
{
    protected static string $resource = JabatanResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
