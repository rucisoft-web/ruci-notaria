<?php

namespace App\Filament\Resources\Berkas\RelationManagers;

use App\Models\TandaTerima;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Tanda terima dokumen asli (misalnya sertipikat yang dititipkan klien). */
class TandaTerimaRelationManager extends RelationManager
{
    protected static string $relationship = 'tandaTerima';

    protected static ?string $title = 'Tanda terima';

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->role?->canWork();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('arah')->label('Arah')->options(['masuk' => 'Diterima dari klien', 'keluar' => 'Diserahkan kepada klien'])->required()->default('masuk'),
            DatePicker::make('tanggal')->label('Tanggal')->default(now())->required(),
            TextInput::make('pihak')->label('Nama yang menyerahkan/menerima')->required()->columnSpanFull(),
            Textarea::make('daftar_dokumen')->label('Daftar dokumen (satu per baris)')->required()->rows(5)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('nomor')->label('Nomor'),
                TextColumn::make('arah')->label('Arah')->badge()->formatStateUsing(fn ($state) => $state === 'masuk' ? 'Masuk' : 'Keluar')
                    ->color(fn ($state) => $state === 'masuk' ? 'info' : 'success'),
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y'),
                TextColumn::make('pihak')->label('Pihak'),
                TextColumn::make('daftar_dokumen')->label('Dokumen')->limit(60)->wrap(),
            ])
            ->headerActions([CreateAction::make()->label('Buat tanda terima')])
            ->recordActions([Action::make('cetak')->label('Cetak')->icon(Heroicon::OutlinedPrinter)
                ->url(fn (TandaTerima $r) => route('cetak.tanda-terima', $r))->openUrlInNewTab()]);
    }
}
