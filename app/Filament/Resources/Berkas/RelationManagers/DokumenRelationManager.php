<?php

namespace App\Filament\Resources\Berkas\RelationManagers;

use App\Filament\Forms\DokumenFields;
use App\Filament\Resources\Subjek\RelationManagers\DokumenRelationManager as DokumenSubjek;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class DokumenRelationManager extends RelationManager
{
    protected static string $relationship = 'dokumen';

    protected static ?string $title = 'Dokumen';

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->role?->canWork();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(DokumenFields::make('berkas/'.$this->getOwnerRecord()->id));
    }

    public function table(Table $table): Table
    {
        return DokumenSubjek::dokumenTable($table);
    }
}
