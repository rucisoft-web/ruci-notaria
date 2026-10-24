<?php

namespace App\Filament\Resources\SuratBawahTangan\Pages;

use App\Filament\Resources\SuratBawahTangan\SuratBawahTanganResource;
use Filament\Resources\Pages\EditRecord;

class EditSuratBawahTangan extends EditRecord
{
    protected static string $resource = SuratBawahTanganResource::class;

    public function getTitle(): string
    {
        return 'Ubah '.$this->getRecord()->nomor;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['jenis'], $data['jabatan_id'], $data['tanggal']);

        return $data;
    }
}
