<?php

namespace App\Filament\Resources\Akta\RelationManagers;

use App\Filament\Resources\Akta\AktaActions;
use App\Models\DrafAkta;
use App\Services\Ai\AiGerbang;
use App\Services\Ai\PemeriksaDrafAi;
use App\Services\PemeriksaDraf;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Versi draf akta. Setiap versi diperiksa pemeriksa deterministik; pemeriksa AI opsional. */
class DrafRelationManager extends RelationManager
{
    protected static string $relationship = 'draf';

    protected static ?string $title = 'Draf';

    public function table(Table $table): Table
    {
        $bisa = fn () => (bool) auth()->user()?->role?->canWork();

        return $table
            ->defaultSort('versi', 'desc')
            ->columns([
                TextColumn::make('versi')->label('Versi')->prefix('v'),
                TextColumn::make('sumber')->label('Sumber')->badge(),
                TextColumn::make('template.nama')->label('Template')->placeholder('—'),
                TextColumn::make('temuan_ringkas')->label('Temuan')
                    ->state(fn (DrafAkta $r) => count($r->temuan ?? []).' pemeriksa'.($r->temuan_ai !== null ? ' · '.count($r->temuan_ai).' AI' : ''))
                    ->color(fn (DrafAkta $r) => $r->jumlahTemuanKritis() > 0 ? 'danger' : 'success'),
                IconColumn::make('final')->label('Final')->boolean(),
                TextColumn::make('createdBy.name')->label('Oleh')->placeholder('—'),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d/m/Y H:i'),
            ])
            ->recordActions([
                Action::make('unduh')->label('Unduh .docx')->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (DrafAkta $r) => route('draf.unduh', $r))->openUrlInNewTab(),
                Action::make('temuan')->label('Temuan')->icon(Heroicon::OutlinedClipboardDocumentList)->color('gray')
                    ->modalHeading(fn (DrafAkta $r) => 'Temuan draf v'.$r->versi)->modalSubmitAction(false)->modalCancelActionLabel('Tutup')
                    ->schema(fn (DrafAkta $r) => [
                        Text::make('Pemeriksa aturan'), Text::make(AktaActions::temuanHtml($r->temuan ?? [])),
                        Text::make('Pemeriksa AI')->visible($r->temuan_ai !== null), Text::make(AktaActions::temuanHtml($r->temuan_ai ?? []))->visible($r->temuan_ai !== null),
                    ]),
                Action::make('periksa_ulang')->label('Periksa ulang')->icon(Heroicon::OutlinedArrowPath)->color('gray')->visible($bisa)
                    ->action(function (DrafAkta $r) {
                        app(PemeriksaDraf::class)->periksa($r);
                        Notification::make()->title('Draf diperiksa ulang: '.count($r->temuan ?? []).' temuan')->success()->send();
                    }),
                Action::make('periksa_ai')->label('Periksa dengan AI')->icon(Heroicon::OutlinedSparkles)->color('gray')
                    ->visible(fn (DrafAkta $r) => $bisa() && app(AiGerbang::class)->boleh('pemeriksa', $this->getOwnerRecord()->berkas))
                    ->requiresConfirmation()
                    ->modalDescription('Teks draf dikirim ke penyedia AI setelah nama, NIK, alamat, dan nomor disamarkan. Temuan AI hanya masukan.')
                    ->action(function (DrafAkta $r) {
                        try {
                            app(PemeriksaDrafAi::class)->periksa($r);
                        } catch (\Throwable $e) {
                            Notification::make()->title('Pemeriksaan AI gagal')->body($e->getMessage())->danger()->send();

                            return;
                        }
                        Notification::make()->title(count($r->fresh()->temuan_ai ?? []).' temuan AI')->success()->send();
                    }),
                Action::make('final')->label('Tandai final')->icon(Heroicon::OutlinedCheckBadge)->color('success')
                    ->visible(fn (DrafAkta $r) => ! $r->final && (bool) auth()->user()?->role?->isNotaris())
                    ->requiresConfirmation()
                    ->modalDescription(fn (DrafAkta $r) => $r->jumlahTemuanKritis() > 0 ? 'Draf ini masih memiliki temuan kritis.' : 'Draf ini menjadi dasar penandatanganan.')
                    ->action(function (DrafAkta $r) {
                        $r->akta->draf()->update(['final' => false]);
                        $r->update(['final' => true]);
                    }),
            ]);
    }
}
