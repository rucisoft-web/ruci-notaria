<?php

namespace App\Filament\Resources\Subjek\Pages;

use App\Filament\Resources\Subjek\AiIsiIdentitas;
use App\Filament\Resources\Subjek\SubjekResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSubjek extends CreateRecord
{
    protected static string $resource = SubjekResource::class;

    protected function getHeaderActions(): array
    {
        return [AiIsiIdentitas::make()];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
