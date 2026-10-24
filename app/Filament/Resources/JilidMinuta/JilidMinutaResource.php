<?php

namespace App\Filament\Resources\JilidMinuta;

use App\Filament\Concerns\ArchivePermissions;
use App\Filament\Resources\JilidMinuta\Pages\ManageJilidMinuta;
use App\Models\Jabatan;
use App\Models\JilidMinuta;
use App\Models\LokasiArsip;
use App\Support\Terbilang;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Jilid minuta bulanan (maks. 50 akta per buku) beserta lokasi simpannya. */
class JilidMinutaResource extends Resource
{
    use ArchivePermissions;

    protected static ?string $model = JilidMinuta::class;

    protected static ?string $slug = 'jilid-minuta';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Buku Daftar & Protokol';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'jilid minuta';

    protected static ?string $pluralModelLabel = 'Jilid Minuta';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('lokasi_arsip_id')->label('Lokasi simpan')->options(fn () => LokasiArsip::options()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('periode')->label('Periode')->state(fn (JilidMinuta $r) => Terbilang::namaBulan($r->bulan).' '.$r->tahun)->weight('bold'),
                TextColumn::make('nomor_jilid')->label('Jilid ke'),
                TextColumn::make('rentang')->label('Akta nomor')->state(fn (JilidMinuta $r) => $r->nomor_awal.' – '.$r->nomor_akhir),
                TextColumn::make('jumlah_minuta')->label('Jumlah minuta'),
                TextColumn::make('lokasi.kode')->label('Lokasi')->placeholder('—')->description(fn (JilidMinuta $r) => $r->lokasi?->nama),
                TextColumn::make('dijilid_at')->label('Dijilid')->date('d/m/Y')->placeholder('Belum'),
                TextColumn::make('jabatan.nama')->label('Notaris')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('jabatan_id')->label('Notaris')->options(fn () => Jabatan::options()),
                SelectFilter::make('tahun')->label('Tahun')->options(fn () => JilidMinuta::query()->distinct()->orderByDesc('tahun')->pluck('tahun', 'tahun')),
            ])
            ->recordActions([
                Action::make('label')->label('Label sampul')->icon(Heroicon::OutlinedPrinter)->color('gray')
                    ->url(fn (JilidMinuta $r) => route('cetak.label-jilid', $r))->openUrlInNewTab(),
                Action::make('dijilid')->label('Sudah dijilid')->icon(Heroicon::OutlinedCheck)->color('success')
                    ->visible(fn (JilidMinuta $r) => ! $r->dijilid_at && static::canArchive())->requiresConfirmation()
                    ->modalDescription('Setelah ditandai, susunan jilid bulan ini tidak dapat diubah.')
                    ->action(fn (JilidMinuta $r) => $r->update(['dijilid_at' => now()])),
                EditAction::make()->label('Lokasi'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageJilidMinuta::route('/')];
    }
}
