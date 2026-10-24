<?php

namespace App\Filament\Resources\JenisLayanan;

use App\Enums\KategoriAkta;
use App\Enums\SifatAkta;
use App\Filament\Concerns\AdminPermissions;
use App\Filament\Resources\JenisLayanan\Pages\ManageJenisLayanan;
use App\Models\JenisLayanan;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Katalog layanan: daftar periksa dokumen, aturan tenggat, sifat/kategori akta, dan honorarium bawaan. */
class JenisLayananResource extends Resource
{
    use AdminPermissions;

    protected static ?string $model = JenisLayanan::class;

    protected static ?string $slug = 'jenis-layanan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'jenis layanan';

    protected static ?string $pluralModelLabel = 'Jenis Layanan';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(3)->components([
            TextInput::make('kode')->label('Kode')->required()->unique(ignoreRecord: true)->maxLength(20),
            TextInput::make('nama')->label('Nama')->required()->columnSpan(2),
            Select::make('kategori')->label('Kategori')->options(JenisLayanan::KATEGORI)->required()->default('akta'),
            Select::make('sifat_akta')->label('Sifat akta')->options(SifatAkta::class)->default(SifatAkta::Minuta),
            Select::make('kategori_akta')->label('Kategori akta')->options(KategoriAkta::class)->default(KategoriAkta::Umum),
            TagsInput::make('checklist')->label('Daftar periksa dokumen')->columnSpanFull()->placeholder('Tambah dokumen lalu Enter'),
            CheckboxList::make('aturan_tenggat')->label('Tenggat yang dibuat saat akta ditandatangani')->columnSpanFull()->columns(2)
                ->options(fn () => collect(config('notaria.tenggat_akta'))->map(fn ($a, $k) => "{$k}: {$a['judul']} ({$a['hari']} hari".($a['hari_kerja'] ? ' kerja' : '').')')->all()),
            TextInput::make('honorarium_bawaan')->label('Honorarium bawaan')->numeric()->prefix('Rp')->default(0),
            TextInput::make('urutan')->label('Urutan')->numeric()->default(100),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->columns([
                TextColumn::make('kode')->label('Kode')->searchable(),
                TextColumn::make('nama')->label('Nama')->searchable()->weight('bold'),
                TextColumn::make('kategori')->label('Kategori')->formatStateUsing(fn ($state) => JenisLayanan::KATEGORI[$state] ?? $state)->badge(),
                TextColumn::make('aturan_tenggat')->label('Tenggat')->badge()->placeholder('—'),
                TextColumn::make('honorarium_bawaan')->label('Honorarium')->formatStateUsing(fn ($state) => Money::format($state)),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->filters([SelectFilter::make('kategori')->options(JenisLayanan::KATEGORI)])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageJenisLayanan::route('/')];
    }
}
