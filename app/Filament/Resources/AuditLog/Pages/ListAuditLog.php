<?php

namespace App\Filament\Resources\AuditLog\Pages;

use App\Filament\Resources\AuditLog\AuditLogResource;
use App\Models\AuditLog;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListAuditLog extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('verifikasi')->label('Verifikasi rantai')->icon(Heroicon::OutlinedLink)
                ->action(function () {
                    $putus = AuditLog::verifyChain();
                    $putus === null
                        ? Notification::make()->success()->title('Rantai jejak audit utuh.')->send()
                        : Notification::make()->danger()->persistent()->title("Rantai putus pada baris #{$putus}.")
                            ->body('Ada data audit yang diubah atau dihapus langsung di database. Laporkan kepada notaris.')->send();
                }),
        ];
    }
}
