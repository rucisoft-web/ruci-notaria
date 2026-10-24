<?php

namespace App\Filament\Resources\Akta\Pages;

use App\Filament\Resources\Akta\AktaActions;
use App\Filament\Resources\Akta\AktaResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewAkta extends ViewRecord
{
    protected static string $resource = AktaResource::class;

    public function getTitle(): string
    {
        return $this->getRecord()->label();
    }

    protected function getHeaderActions(): array
    {
        return [
            AktaActions::beriNomor(),
            AktaActions::jadwalkan(),
            AktaActions::buatDraf(),
            ActionGroup::make([
                AktaActions::periksaKesiapan(), AktaActions::unggahDraf(), AktaActions::lembarTandaTangan(), AktaActions::batalkan(),
            ])->label('Lainnya')->icon(Heroicon::OutlinedEllipsisVertical)->button()->color('gray'),
            EditAction::make(),
        ];
    }
}
