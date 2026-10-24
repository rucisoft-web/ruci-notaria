<?php

namespace App\Filament\Resources\SuratBawahTangan\Pages;

use App\Filament\Resources\SuratBawahTangan\SuratBawahTanganResource;
use App\Services\PenomoranSurat;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSuratBawahTangan extends CreateRecord
{
    protected static string $resource = SuratBawahTanganResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(PenomoranSurat::class)->catat($data);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()->title('Tercatat dengan nomor '.$this->getRecord()->nomor)->success();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
