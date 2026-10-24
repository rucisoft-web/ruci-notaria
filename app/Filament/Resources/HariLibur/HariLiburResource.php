<?php

namespace App\Filament\Resources\HariLibur;

use App\Filament\Concerns\AdminPermissions;
use App\Filament\Resources\HariLibur\Pages\ManageHariLibur;
use App\Models\HariLibur;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Libur nasional dan cuti bersama; dipakai menghitung tenggat hari kerja (misal 7 hari kerja penyampaian akta PPAT). */
class HariLiburResource extends Resource
{
    use AdminPermissions;

    protected static ?string $model = HariLibur::class;

    protected static ?string $slug = 'hari-libur';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 60;

    protected static ?string $modelLabel = 'hari libur';

    protected static ?string $pluralModelLabel = 'Hari Libur';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('tanggal')->label('Tanggal')->required()->unique(ignoreRecord: true),
            TextInput::make('nama')->label('Keterangan')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('tanggal')->label('Tanggal')->date('l, d F Y')->sortable(),
                TextColumn::make('nama')->label('Keterangan'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageHariLibur::route('/')];
    }
}
