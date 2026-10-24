<?php

namespace App\Filament\Resources\Tagihan;

use App\Enums\StatusTagihan;
use App\Filament\Concerns\WithRole;
use App\Filament\Resources\Tagihan\Pages\CreateTagihan;
use App\Filament\Resources\Tagihan\Pages\EditTagihan;
use App\Filament\Resources\Tagihan\Pages\ListTagihan;
use App\Filament\Resources\Tagihan\Pages\ViewTagihan;
use App\Models\Berkas;
use App\Models\Tagihan;
use App\Models\TagihanItem;
use App\Services\Honorarium;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Tagihan klien. Honorarium dipisahkan dari titipan pihak ketiga dan dibatasi Pasal 36 UUJN. */
class TagihanResource extends Resource
{
    use WithRole;

    protected static ?string $model = Tagihan::class;

    protected static ?string $slug = 'tagihan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Keuangan';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'tagihan';

    protected static ?string $pluralModelLabel = 'Tagihan';

    public static function canViewAny(): bool
    {
        return (bool) static::role()?->canViewFinance();
    }

    public static function canCreate(): bool
    {
        return static::canFinance();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canFinance();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('berkas.klien')->withSum('items as total_items', 'jumlah')->withSum('pembayaran as total_bayar', 'jumlah');
    }

    public static function form(Schema $schema): Schema
    {
        $terkunci = fn (?Tagihan $record) => $record?->exists && $record->status !== StatusTagihan::Draf;

        return $schema->components([
            Section::make('Tagihan')->columns(3)->columnSpanFull()->schema([
                Select::make('berkas_id')->label('Berkas')->required()->searchable()
                    ->options(fn () => Berkas::query()->terlihatOleh(auth()->user())->with('klien')->latest()->limit(200)->get()
                        ->mapWithKeys(fn (Berkas $b) => [$b->id => $b->nomor.' · '.$b->klien?->nama]))
                    ->disabledOn('edit')->live(),
                DatePicker::make('tanggal')->label('Tanggal')->default(now())->required()->disabled($terkunci),
                DatePicker::make('jatuh_tempo')->label('Jatuh tempo')->disabled($terkunci),
                Text::make(fn (Get $get, ?Tagihan $record) => static::keteranganBatas($get('berkas_id'), $record))->columnSpanFull(),
            ]),
            Section::make('Rincian')->columnSpanFull()->schema([
                Repeater::make('items')->relationship()->hiddenLabel()->defaultItems(1)->columns(6)->disabled($terkunci)
                    ->addActionLabel('Tambah baris')
                    ->schema([
                        Select::make('jenis')->label('Jenis')->options(TagihanItem::JENIS)->required()->default('honorarium')->columnSpan(2),
                        TextInput::make('uraian')->label('Uraian')->required()->columnSpan(2),
                        TextInput::make('jumlah')->label('Jumlah (Rp)')->numeric()->minValue(0)->required()->columnSpan(2),
                    ]),
            ]),
            Textarea::make('catatan')->label('Catatan')->rows(2)->columnSpanFull()->disabled($terkunci),
        ]);
    }

    public static function keteranganBatas(?int $berkasId, ?Tagihan $record): string
    {
        $berkas = $berkasId ? Berkas::find($berkasId) : null;
        if (! $berkas) {
            return 'Pilih berkas untuk melihat batas honorarium Pasal 36 UUJN.';
        }

        return 'Batas honorarium berkas: '.Money::format(Honorarium::batasBerkas($berkas))
            .' · sudah ditagih di tagihan lain: '.Money::format(Honorarium::sudahDitagih($berkas, $record?->id))
            .($berkas->cuma_cuma ? ' · berkas cuma-cuma' : '');
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('tanggal', 'desc')
            ->columns([
                TextColumn::make('nomor')->label('Nomor')->searchable(),
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y')->sortable(),
                TextColumn::make('berkas.nomor')->label('Berkas')->description(fn (Tagihan $r) => $r->berkas?->klien?->nama)->searchable(),
                TextColumn::make('total_items')->label('Total')->formatStateUsing(fn ($state) => Money::format($state))->alignEnd(),
                TextColumn::make('sisa')->label('Sisa')->alignEnd()
                    ->state(fn (Tagihan $r) => Money::format((float) $r->total_items - (float) $r->total_bayar)),
                TextColumn::make('jatuh_tempo')->label('Jatuh tempo')->date('d/m/Y')->placeholder('-')
                    ->color(fn (Tagihan $r) => $r->status === StatusTagihan::Terbit && $r->jatuh_tempo?->isPast() ? 'danger' : null),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->filters([SelectFilter::make('status')->label('Status')->options(StatusTagihan::options())])
            ->recordActions([
                Action::make('cetak')->label('Cetak')->icon(Heroicon::OutlinedPrinter)->url(fn (Tagihan $r) => route('cetak.tagihan', $r))->openUrlInNewTab(),
                EditAction::make()->label('Buka'),
            ]);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\PembayaranRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTagihan::route('/'),
            'create' => CreateTagihan::route('/buat'),
            'view' => ViewTagihan::route('/{record}/lihat'),
            'edit' => EditTagihan::route('/{record}'),
        ];
    }
}
