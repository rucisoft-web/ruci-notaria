<?php

namespace App\Filament\Resources\SerahTerimaProtokol;

use App\Filament\Concerns\ArchivePermissions;
use App\Filament\Resources\SerahTerimaProtokol\Pages\ManageSerahTerimaProtokol;
use App\Models\SerahTerimaProtokol;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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

class SerahTerimaProtokolResource extends Resource
{
    use ArchivePermissions;

    protected static ?string $model = SerahTerimaProtokol::class;

    protected static ?string $slug = 'serah-terima-protokol';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Buku Daftar & Protokol';

    protected static ?int $navigationSort = 50;

    protected static ?string $modelLabel = 'serah terima protokol';

    protected static ?string $pluralModelLabel = 'Serah Terima Protokol';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('arah')->label('Arah')->options(['masuk' => 'Masuk (kantor ini menerima)', 'keluar' => 'Keluar (kantor ini menyerahkan)'])->required(),
            Select::make('alasan')->label('Alasan')->options(SerahTerimaProtokol::ALASAN)->required(),
            TextInput::make('notaris_asal')->label('Notaris yang menyerahkan')->required(),
            TextInput::make('notaris_penerima')->label('Notaris penerima / MPD')->required(),
            DatePicker::make('tanggal')->label('Tanggal serah terima')->required(),
            TextInput::make('nomor_berita_acara')->label('Nomor berita acara'),
            TextInput::make('tahun_awal')->label('Protokol dari tahun')->numeric(),
            TextInput::make('tahun_akhir')->label('sampai tahun')->numeric(),
            TextInput::make('jumlah_buku')->label('Jumlah buku/bundel')->numeric()->default(0),
            Toggle::make('ada_protokol_25_tahun')->label('Ada protokol berumur 25 tahun atau lebih (diserahkan ke MPD, Pasal 63 ayat 5)'),
            Textarea::make('catatan')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y'),
                TextColumn::make('arah')->label('Arah')->badge(),
                TextColumn::make('alasan')->label('Alasan')->formatStateUsing(fn ($state) => SerahTerimaProtokol::ALASAN[$state] ?? $state)->wrap(),
                TextColumn::make('notaris_asal')->label('Dari'),
                TextColumn::make('notaris_penerima')->label('Kepada'),
                TextColumn::make('rentang')->label('Tahun')->state(fn (SerahTerimaProtokol $record) => trim($record->tahun_awal.' – '.$record->tahun_akhir, ' –')),
                IconColumn::make('ada_protokol_25_tahun')->label('≥ 25 th')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageSerahTerimaProtokol::route('/')];
    }
}
