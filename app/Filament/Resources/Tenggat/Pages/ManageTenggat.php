<?php

namespace App\Filament\Resources\Tenggat\Pages;

use App\Filament\Resources\Tenggat\TenggatResource;
use App\Services\KalkulatorTenggat;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageTenggat extends ManageRecords
{
    protected static string $resource = TenggatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bulanan')->label('Buat tenggat bulan lalu')->icon(Heroicon::OutlinedArrowPath)->color('gray')
                ->tooltip('Biasanya berjalan otomatis lewat cron; tombol ini untuk hosting tanpa cron.')
                ->action(function () {
                    $n = app(KalkulatorTenggat::class)->buatBulanan(now()->subMonth());
                    $n += app(KalkulatorTenggat::class)->buatBulanan(now());
                    Notification::make()->title($n ? "{$n} tenggat bulanan dibuat." : 'Tenggat bulanan sudah ada.')->success()->send();
                }),
        ];
    }
}
