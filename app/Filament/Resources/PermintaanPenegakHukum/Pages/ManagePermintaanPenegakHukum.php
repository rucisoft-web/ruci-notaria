<?php

namespace App\Filament\Resources\PermintaanPenegakHukum\Pages;

use App\Filament\Resources\PermintaanPenegakHukum\PermintaanPenegakHukumResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePermintaanPenegakHukum extends ManageRecords
{
    protected static string $resource = PermintaanPenegakHukumResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
