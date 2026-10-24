<?php

namespace App\Filament\Resources\Tagihan\Pages;

use App\Enums\StatusTagihan;
use App\Filament\Resources\Tagihan\TagihanResource;
use App\Models\Tagihan;
use App\Services\Honorarium;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

/** Tagihan draf dapat diubah; setelah terbit rinciannya terkunci dan hanya menerima pembayaran. */
class EditTagihan extends EditRecord
{
    protected static string $resource = TagihanResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Tagihan $t */
        $t = $this->getRecord();

        return [
            Action::make('terbit')->label('Terbitkan')->icon(Heroicon::OutlinedPaperAirplane)->color('success')
                ->visible($t->status === StatusTagihan::Draf)->requiresConfirmation()
                ->modalDescription('Rincian tagihan terkunci setelah terbit.')
                ->action(function () use ($t) {
                    if ($t->items()->count() === 0) {
                        Notification::make()->danger()->title('Tagihan belum memiliki rincian.')->send();

                        return;
                    }
                    if ($pesan = Honorarium::periksa($t->berkas, $t->totalHonorarium(), $t->id)) {
                        Notification::make()->danger()->title($pesan)->send();

                        return;
                    }
                    $t->update(['status' => StatusTagihan::Terbit]);
                    $t->sinkronStatus();
                    $this->redirect(TagihanResource::getUrl('edit', ['record' => $t]));
                }),
            Action::make('cetak')->label('Cetak')->icon(Heroicon::OutlinedPrinter)->color('gray')->url(route('cetak.tagihan', $t))->openUrlInNewTab(),
            Action::make('batal')->label('Batalkan')->icon(Heroicon::OutlinedXCircle)->color('danger')
                ->visible(in_array($t->status, [StatusTagihan::Draf, StatusTagihan::Terbit], true) && $t->pembayaran()->doesntExist())
                ->requiresConfirmation()
                ->action(function () use ($t) {
                    $t->update(['status' => StatusTagihan::Batal]);
                    $this->redirect(TagihanResource::getUrl('edit', ['record' => $t]));
                }),
        ];
    }

    public function getTitle(): string
    {
        return 'Tagihan '.$this->getRecord()->nomor;
    }

    protected function getFormActions(): array
    {
        return $this->getRecord()->status === StatusTagihan::Draf ? parent::getFormActions() : [];
    }

    protected function beforeSave(): void
    {
        if ($this->getRecord()->status !== StatusTagihan::Draf) {
            throw ValidationException::withMessages(['data.items' => 'Tagihan yang sudah terbit tidak dapat diubah.']);
        }
        $honor = collect($this->form->getRawState()['items'] ?? [])->where('jenis', 'honorarium')->sum(fn ($i) => (float) ($i['jumlah'] ?? 0));
        if ($pesan = Honorarium::periksa($this->getRecord()->berkas, $honor, $this->getRecord()->id)) {
            throw ValidationException::withMessages(['data.items' => $pesan]);
        }
    }
}
