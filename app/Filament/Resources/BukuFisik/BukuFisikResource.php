<?php

namespace App\Filament\Resources\BukuFisik;

use App\Enums\JenisDaftar;
use App\Filament\Concerns\ArchivePermissions;
use App\Filament\Resources\BukuFisik\Pages\ManageBukuFisik;
use App\Models\BukuFisik;
use App\Models\Jabatan;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Buku daftar fisik yang telah diparaf MPD; entri mencatat halaman tempat dicetak/disalin. */
class BukuFisikResource extends Resource
{
    use ArchivePermissions;

    protected static ?string $model = BukuFisik::class;

    protected static ?string $slug = 'buku-fisik';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Buku Daftar & Protokol';

    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'buku fisik';

    protected static ?string $pluralModelLabel = 'Buku Daftar Fisik';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('jabatan_id')->label('Notaris')->options(fn () => Jabatan::options())->required()->default(fn () => Jabatan::utama()?->id),
            Select::make('jenis_daftar')->label('Jenis daftar')->options(JenisDaftar::class)->required(),
            TextInput::make('nomor_buku')->label('Nomor buku')->required(),
            TextInput::make('jumlah_halaman')->label('Jumlah halaman')->numeric()->required(),
            DatePicker::make('tanggal_paraf_mpd')->label('Tanggal diparaf MPD'),
            DatePicker::make('mulai_dipakai')->label('Mulai dipakai'),
            Toggle::make('aktif')->label('Sedang dipakai')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('jenis_daftar')->label('Jenis')->badge(),
                TextColumn::make('nomor_buku')->label('Nomor buku'),
                TextColumn::make('jumlah_halaman')->label('Halaman'),
                TextColumn::make('tanggal_paraf_mpd')->label('Paraf MPD')->date('d/m/Y')->placeholder('Belum'),
                TextColumn::make('mulai_dipakai')->label('Mulai')->date('d/m/Y')->placeholder('—'),
                IconColumn::make('aktif')->label('Aktif')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBukuFisik::route('/')];
    }
}
