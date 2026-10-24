<?php

namespace App\Filament\Resources\Subjek\RelationManagers;

use App\Filament\Forms\DokumenFields;
use App\Models\Dokumen;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DokumenRelationManager extends RelationManager
{
    protected static string $relationship = 'dokumen';

    protected static ?string $title = 'Dokumen identitas';

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->role?->canWork();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(DokumenFields::make('subjek/'.$this->getOwnerRecord()->id));
    }

    public function table(Table $table): Table
    {
        return static::dokumenTable($table);
    }

    public static function dokumenTable(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('jenis')->label('Jenis')->formatStateUsing(fn ($state) => Dokumen::JENIS[$state] ?? $state)->badge(),
                TextColumn::make('judul')->label('Judul'),
                TextColumn::make('berlaku_sampai')->label('Berlaku sampai')->date('d/m/Y')->placeholder('—')
                    ->color(fn (Dokumen $r) => $r->berlaku_sampai?->isPast() ? 'danger' : null),
                TextColumn::make('uploader.name')->label('Diunggah oleh')->placeholder('—'),
                TextColumn::make('created_at')->label('Tanggal')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([CreateAction::make()->label('Unggah dokumen')
                ->mutateDataUsing(fn (array $data) => $data + ['uploaded_by' => auth()->id()])])
            ->recordActions([
                Action::make('unduh')->label('Unduh')->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (Dokumen $r) => route('dokumen.unduh', $r))->openUrlInNewTab(),
            ]);
    }
}
