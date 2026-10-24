<?php

namespace App\Filament\Resources\LokasiArsip;

use App\Filament\Concerns\ArchivePermissions;
use App\Filament\Resources\LokasiArsip\Pages\ManageLokasiArsip;
use App\Models\LokasiArsip;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class LokasiArsipResource extends Resource
{
    use ArchivePermissions;

    protected static ?string $model = LokasiArsip::class;

    protected static ?string $slug = 'lokasi-arsip';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|UnitEnum|null $navigationGroup = 'Buku Daftar & Protokol';

    protected static ?int $navigationSort = 70;

    protected static ?string $modelLabel = 'lokasi arsip';

    protected static ?string $pluralModelLabel = 'Lokasi Arsip';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('kode')->label('Kode')->required()->unique(ignoreRecord: true)->placeholder('R1-L2-RK3'),
            TextInput::make('nama')->label('Nama')->required()->placeholder('Ruang arsip 1, lemari 2, rak 3'),
            Textarea::make('keterangan')->label('Keterangan')->columnSpanFull(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('kode')
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable()->weight('bold'),
                TextColumn::make('nama')->label('Nama')->searchable(),
                TextColumn::make('jilid_count')->label('Jilid')->counts('jilid'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageLokasiArsip::route('/')];
    }
}
