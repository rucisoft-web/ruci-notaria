<?php

namespace App\Filament\Resources\Subjek\RelationManagers;

use App\Enums\Hubungan;
use App\Models\Subjek;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Hubungan keluarga dan hubungan lain; dipakai pemeriksaan Pasal 40 dan 52 UUJN. */
class RelasiRelationManager extends RelationManager
{
    protected static string $relationship = 'relasi';

    protected static ?string $title = 'Hubungan';

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->role?->canWork();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('hubungan')->label('Orang ini adalah … dari dirinya')->options(Hubungan::class)->required(),
            Select::make('terkait_id')->label('Terkait dengan')->required()->searchable()
                ->getSearchResultsUsing(fn (string $search) => Subjek::query()->cari($search)->whereKeyNot($this->getOwnerRecord()->id)->limit(30)->pluck('nama', 'id'))
                ->getOptionLabelUsing(fn ($value) => Subjek::find($value)?->nama),
            TextInput::make('catatan')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Contoh: subjek ini "Suami/istri" dari X. Hubungan juga terbaca dari arah sebaliknya.')
            ->columns([
                TextColumn::make('hubungan')->label('Hubungan')->badge(),
                TextColumn::make('terkait.nama')->label('Dengan'),
                TextColumn::make('catatan')->label('Catatan')->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()->label('Tambah hubungan')])
            ->recordActions([DeleteAction::make()]);
    }
}
