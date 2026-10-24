<?php

namespace App\Filament\Resources\Subjek\Pages;

use App\Filament\Resources\Subjek\SubjekResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSubjek extends ViewRecord
{
    protected static string $resource = SubjekResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
