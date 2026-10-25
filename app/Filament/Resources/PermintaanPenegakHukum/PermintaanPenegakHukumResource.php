<?php

namespace App\Filament\Resources\PermintaanPenegakHukum;

use App\Filament\Concerns\WithRole;
use App\Filament\Resources\PermintaanPenegakHukum\Pages\ManagePermintaanPenegakHukum;
use App\Models\Akta;
use App\Models\PermintaanPenegakHukum;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Register pemanggilan notaris dan pengambilan fotokopi minuta oleh penegak hukum (Pasal 66 UUJN). Hanya notaris. */
class PermintaanPenegakHukumResource extends Resource
{
    use WithRole;

    protected static ?string $model = PermintaanPenegakHukum::class;

    protected static ?string $slug = 'permintaan-penegak-hukum';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static string|UnitEnum|null $navigationGroup = 'Buku Daftar & Protokol';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'permintaan penegak hukum';

    protected static ?string $pluralModelLabel = 'Permintaan Penegak Hukum';

    public static function canViewAny(): bool
    {
        return static::isNotaris() || static::isTamu();
    }

    public static function canCreate(): bool
    {
        return static::isNotaris();
    }

    public static function canEdit(Model $record): bool
    {
        return static::isNotaris();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('jenis')->label('Jenis')->options(PermintaanPenegakHukum::JENIS)->required(),
            Select::make('akta_id')->label('Akta terkait')->searchable()
                ->getSearchResultsUsing(fn (string $s) => Akta::query()->bernomor()->where('judul', 'like', "%{$s}%")->orWhere('nomor_bulanan', $s)->limit(20)->get()->mapWithKeys(fn ($a) => [$a->id => $a->label()]))
                ->getOptionLabelUsing(fn ($v) => Akta::find($v)?->label()),
            TextInput::make('instansi')->label('Instansi (penyidik/penuntut umum/hakim)')->required(),
            TextInput::make('nomor_surat')->label('Nomor surat')->required(),
            DatePicker::make('tanggal_surat')->label('Tanggal surat')->required(),
            TextInput::make('nomor_persetujuan_mkn')->label('Nomor persetujuan MKN'),
            DatePicker::make('tanggal_persetujuan_mkn')->label('Tanggal persetujuan MKN'),
            DatePicker::make('tanggal_pelaksanaan')->label('Tanggal pelaksanaan'),
            Textarea::make('catatan')->label('Catatan')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tanggal_surat', 'desc')
            ->columns([
                TextColumn::make('tanggal_surat')->label('Tanggal')->date('d/m/Y'),
                TextColumn::make('jenis')->label('Jenis')->formatStateUsing(fn ($state) => PermintaanPenegakHukum::JENIS[$state] ?? $state)->badge(),
                TextColumn::make('instansi')->label('Instansi')->wrap(),
                TextColumn::make('nomor_surat')->label('Nomor surat'),
                TextColumn::make('akta.judul')->label('Akta')->placeholder('—')->wrap(),
                TextColumn::make('nomor_persetujuan_mkn')->label('Persetujuan MKN')->placeholder('Belum ada')
                    ->color(fn ($state) => $state ? null : 'danger'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePermintaanPenegakHukum::route('/')];
    }
}
