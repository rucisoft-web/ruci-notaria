<?php

namespace App\Filament\Resources\Berkas\Pages;

use App\Enums\StatusBerkas;
use App\Filament\Resources\Berkas\BerkasResource;
use App\Models\Berkas;
use App\Support\Money;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewBerkas extends ViewRecord
{
    protected static string $resource = BerkasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ubah_tahap')->label('Ubah tahap')->icon(Heroicon::OutlinedArrowPath)->color('gray')
                ->visible(fn () => auth()->user()->role->canWork())
                ->schema([Select::make('status')->label('Tahap baru')->options(StatusBerkas::class)->required()
                    ->default(fn (Berkas $record) => $record->status)])
                ->action(function (Berkas $record, array $data) {
                    $baru = StatusBerkas::from($data['status'] instanceof StatusBerkas ? $data['status']->value : $data['status']);
                    if ($baru === StatusBerkas::Selesai && abs($record->saldoTitipan()) >= 0.01) {
                        Notification::make()->title('Saldo dana titipan masih '.Money::format($record->saldoTitipan()))
                            ->body('Selesaikan atau kembalikan dana titipan sebelum menutup berkas.')->danger()->send();

                        return;
                    }
                    $record->update(['status' => $baru, 'tanggal_selesai' => $baru === StatusBerkas::Selesai ? now()->toDateString() : $record->tanggal_selesai]);
                    Notification::make()->title('Tahap berkas: '.$baru->getLabel())->success()->send();
                }),
            EditAction::make(),
        ];
    }
}
