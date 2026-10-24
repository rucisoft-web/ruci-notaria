<?php

namespace App\Filament\Resources\Subjek\Pages;

use App\Filament\Resources\Subjek\AiIsiIdentitas;
use App\Filament\Resources\Subjek\SubjekResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubjek extends EditRecord
{
    protected static string $resource = SubjekResource::class;

    protected function getHeaderActions(): array
    {
        return [AiIsiIdentitas::make(), DeleteAction::make()];
    }
}
