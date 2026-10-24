<?php

namespace App\Filament\Resources\Subjek\Pages;

use App\Filament\Resources\Subjek\SubjekResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSubjek extends ListRecords
{
    protected static string $resource = SubjekResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
