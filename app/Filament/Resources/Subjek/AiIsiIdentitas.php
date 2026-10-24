<?php

namespace App\Filament\Resources\Subjek;

use App\Services\Ai\AiGerbang;
use App\Services\Ai\EkstraksiIdentitas;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

/** Tombol "Isi dari foto identitas (AI)" pada halaman buat/ubah klien. Hasil mengisi formulir, belum disimpan. */
class AiIsiIdentitas
{
    public static function make(): Action
    {
        return Action::make('ai_isi_identitas')
            ->label('Isi dari foto identitas (AI)')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('gray')
            ->visible(fn () => app(AiGerbang::class)->boleh('ekstraksi'))
            ->modalHeading('Baca KTP/paspor dengan AI')
            ->modalDescription('Gambar dikirim ke penyedia AI (OpenAI) untuk dibaca. Pastikan klien sudah memberi persetujuan. Hasil mengisi formulir dan wajib Anda cocokkan sebelum disimpan.')
            ->modalSubmitActionLabel('Baca')
            ->schema([
                FileUpload::make('berkas')->label('Foto atau PDF identitas')->required()->disk('local')->directory('tmp-ai')->visibility('private')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])->maxSize(8192),
            ])
            ->action(function (array $data, $livewire) {
                $disk = Storage::disk('local');
                $path = $disk->path($data['berkas']);

                try {
                    $hasil = app(EkstraksiIdentitas::class)->baca($path, (string) $disk->mimeType($data['berkas']));
                } catch (\Throwable $e) {
                    Notification::make()->title('AI tidak dapat membaca dokumen')->body($e->getMessage())->danger()->send();

                    return;
                } finally {
                    $disk->delete($data['berkas']);
                }

                $isi = array_filter($hasil['data'], fn ($v) => $v !== null && $v !== '');
                $livewire->data = array_merge($livewire->data ?? [], $isi, ['jenis' => 'orang', 'persetujuan_ai' => true]);

                Notification::make()->title(count($isi).' kolom terisi dari AI. Cocokkan dengan dokumen sebelum menyimpan.')
                    ->body(implode(' ', $hasil['peringatan']) ?: null)
                    ->warning()->persistent()->send();
            });
    }
}
