<?php

namespace App\Filament\Resources\TemplateAkta\Pages;

use App\Enums\StatusTemplate;
use App\Filament\Resources\TemplateAkta\TemplateAktaResource;
use App\Models\TemplateAkta;
use App\Services\TemplateBawaan;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageTemplateAkta extends ManageRecords
{
    protected static string $resource = TemplateAktaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('variabel')->label('Daftar variabel')->icon(Heroicon::OutlinedQuestionMarkCircle)->color('gray')
                ->modalHeading('Variabel template')->modalSubmitAction(false)->modalCancelActionLabel('Tutup')
                ->schema(TemplateAktaResource::daftarVariabel()),
            Action::make('bawaan')->label('Buat template contoh')->icon(Heroicon::OutlinedSparkles)->color('gray')
                ->requiresConfirmation()
                ->modalDescription('Membuat template akta umum berisi kepala akta, komparisi, saksi, dan penutup dengan variabel. Isi akta tetap harus disusun dan disetujui notaris.')
                ->action(function () {
                    $versi = (int) TemplateAkta::where('nama', 'Akta Umum (contoh)')->max('versi') + 1;
                    $path = app(TemplateBawaan::class)->aktaUmum("template/akta-umum-v{$versi}.docx");
                    TemplateAkta::create(['nama' => 'Akta Umum (contoh)', 'versi' => $versi, 'path' => $path, 'status' => StatusTemplate::Draf,
                        'catatan' => 'Template contoh bawaan aplikasi.']);
                    Notification::make()->success()->title('Template contoh dibuat sebagai draf.')->send();
                }),
            CreateAction::make()->mutateDataUsing(fn (array $data) => $data + ['versi' => 1, 'status' => StatusTemplate::Draf]),
        ];
    }
}
