<?php

namespace App\Filament\Resources\Akta\Pages;

use App\Filament\Resources\Akta\AktaResource;
use App\Models\Akta;
use Filament\Resources\Pages\EditRecord;

class EditAkta extends EditRecord
{
    protected static string $resource = AktaResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->getRecord()->isBernomor()) {
            foreach (Akta::TERKUNCI as $field) {
                unset($data[$field]);
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
