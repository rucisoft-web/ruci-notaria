<?php

namespace App\Filament\Resources\JilidMinuta\Pages;

use App\Filament\Pages\BukuDaftar;
use App\Filament\Resources\JilidMinuta\JilidMinutaResource;
use App\Models\Jabatan;
use App\Models\LokasiArsip;
use App\Services\PenjilidanMinuta;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class ManageJilidMinuta extends ManageRecords
{
    protected static string $resource = JilidMinutaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('susun')->label('Susun jilid bulan')->icon(Heroicon::OutlinedSquare3Stack3d)
                ->visible(fn () => auth()->user()->role->canArchive())
                ->fillForm(['jabatan_id' => Jabatan::utama()?->id, 'tahun' => now()->subMonth()->year, 'bulan' => now()->subMonth()->month])
                ->schema([
                    Select::make('jabatan_id')->label('Notaris')->options(fn () => Jabatan::options())->required(),
                    Select::make('tahun')->label('Tahun')->options(array_combine($t = range(now()->year, now()->year - 5), $t))->required(),
                    Select::make('bulan')->label('Bulan')->options(BukuDaftar::BULAN)->required(),
                    Select::make('lokasi_arsip_id')->label('Lokasi simpan')->options(fn () => LokasiArsip::options()),
                ])
                ->action(function (array $data, Action $action) {
                    try {
                        $jilid = app(PenjilidanMinuta::class)->susun((int) $data['jabatan_id'], (int) $data['tahun'], (int) $data['bulan'], $data['lokasi_arsip_id'] ?? null);
                    } catch (ValidationException $e) {
                        Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
                        $action->halt();

                        return;
                    }
                    Notification::make()->title(count($jilid).' jilid disusun')->body('Cetak label sampul, jilid fisik, lalu tandai "Sudah dijilid".')->success()->send();
                }),
        ];
    }
}
