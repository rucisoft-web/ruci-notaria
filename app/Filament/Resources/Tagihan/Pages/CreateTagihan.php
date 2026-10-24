<?php

namespace App\Filament\Resources\Tagihan\Pages;

use App\Filament\Resources\Tagihan\TagihanResource;
use App\Models\Berkas;
use App\Services\Honorarium;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateTagihan extends CreateRecord
{
    protected static string $resource = TagihanResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->form->getRawState();
        $honor = collect($data['items'] ?? [])->where('jenis', 'honorarium')->sum(fn ($i) => (float) ($i['jumlah'] ?? 0));
        if ($pesan = Honorarium::periksa(Berkas::findOrFail($data['berkas_id']), $honor)) {
            throw ValidationException::withMessages(['data.items' => $pesan]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
