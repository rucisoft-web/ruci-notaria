<?php

namespace App\Filament\Resources\Berkas\RelationManagers;

use App\Filament\Resources\Berkas\BerkasResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PihakRelationManager extends RelationManager
{
    protected static string $relationship = 'pihak';

    protected static ?string $title = 'Pihak';

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->role?->canWork();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            BerkasResource::klienSelect('subjek_id', 'Orang/badan')->required(),
            TextInput::make('peran')->label('Peran')->required()->datalist(['Penjual', 'Pembeli', 'Pemberi kuasa', 'Penerima kuasa', 'Pendiri', 'Direktur', 'Komisaris', 'Debitur', 'Kreditur', 'Pemberi fidusia', 'Penerima fidusia', 'Pewaris', 'Ahli waris', 'Pemberi hibah', 'Penerima hibah']),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subjek.nama')->label('Nama')->weight('bold'),
                TextColumn::make('peran')->label('Peran')->badge(),
                TextColumn::make('subjek.telepon')->label('Telepon')->placeholder('—'),
            ])
            ->headerActions([CreateAction::make()->label('Tambah pihak')])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
