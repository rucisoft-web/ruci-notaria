<?php

namespace App\Filament\Resources\Akta\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Berita acara pembetulan kesalahan tulis/ketik pada minuta (Pasal 51 UUJN). */
class PembetulanRelationManager extends RelationManager
{
    protected static string $relationship = 'beritaAcaraPembetulan';

    protected static ?string $title = 'Berita acara pembetulan';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->isBernomor();
    }

    public function isReadOnly(): bool
    {
        return ! auth()->user()?->role?->isNotaris();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('tanggal')->label('Tanggal berita acara')->default(now())->required(),
            Textarea::make('kesalahan')->label('Kesalahan tulis/ketik')->required()->columnSpanFull(),
            Textarea::make('pembetulan')->label('Pembetulan')->required()->columnSpanFull(),
            Textarea::make('disampaikan_kepada')->label('Salinan berita acara disampaikan kepada')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y'),
                TextColumn::make('kesalahan')->label('Kesalahan')->wrap()->limit(80),
                TextColumn::make('pembetulan')->label('Pembetulan')->wrap()->limit(80),
            ])
            ->headerActions([CreateAction::make()->label('Buat berita acara')]);
    }
}
