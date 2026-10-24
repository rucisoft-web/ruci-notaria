<?php

namespace App\Filament\Resources\Berkas;

use App\Enums\StatusBerkas;
use App\Filament\Concerns\WithRole;
use App\Filament\Resources\Berkas\Pages\CreateBerkas;
use App\Filament\Resources\Berkas\Pages\EditBerkas;
use App\Filament\Resources\Berkas\Pages\ListBerkas;
use App\Filament\Resources\Berkas\Pages\ViewBerkas;
use App\Filament\Resources\Subjek\SubjekResource;
use App\Models\Berkas;
use App\Models\Jabatan;
use App\Models\JenisLayanan;
use App\Models\Subjek;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class BerkasResource extends Resource
{
    use WithRole;

    protected static ?string $model = Berkas::class;

    protected static ?string $slug = 'berkas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|UnitEnum|null $navigationGroup = 'Kerja Harian';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'berkas';

    protected static ?string $pluralModelLabel = 'Berkas';

    protected static ?string $recordTitleAttribute = 'nomor';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->terlihatOleh(static::user());
    }

    public static function canViewAny(): bool
    {
        return static::canViewFiles();
    }

    public static function canCreate(): bool
    {
        return static::canWork();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canWork();
    }

    public static function canDelete(Model $record): bool
    {
        return static::isNotaris() && ! $record->akta()->exists() && ! $record->tagihan()->exists() && ! $record->danaTitipan()->exists();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function klienSelect(string $name = 'klien_id', string $label = 'Klien (penanggung biaya)'): Select
    {
        return Select::make($name)->label($label)->searchable()
            ->getSearchResultsUsing(fn (string $search) => Subjek::query()->cari($search)->limit(30)->pluck('nama', 'id'))
            ->getOptionLabelUsing(fn ($value) => Subjek::find($value)?->nama)
            ->createOptionForm([
                Select::make('jenis')->label('Jenis')->options(['orang' => 'Orang', 'badan' => 'Badan'])->default('orang')->required(),
                TextInput::make('nama')->label('Nama')->required(),
                TextInput::make('telepon')->label('Telepon/WhatsApp'),
            ])
            ->createOptionUsing(fn (array $data) => Subjek::create($data + ['created_by' => auth()->id()])->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Berkas')->columns(4)->columnSpanFull()->schema([
                TextInput::make('judul')->label('Judul berkas')->required()->maxLength(255)->columnSpan(2)
                    ->placeholder('Misal: Pendirian PT Maju Bersama'),
                Select::make('jenis_layanan_id')->label('Jenis layanan')->options(fn () => JenisLayanan::options())->required()->searchable()
                    ->disabledOn('edit')->helperText('Menentukan daftar periksa dokumen dan tenggat.'),
                Select::make('jabatan_id')->label('Notaris')->options(fn () => Jabatan::options())->required()
                    ->default(fn () => Jabatan::utama()?->id),
                static::klienSelect(),
                Select::make('pic_id')->label('PIC')->options(fn () => static::userOptions())->default(fn () => auth()->id()),
                Select::make('status')->label('Tahap')->options(StatusBerkas::class)->default(StatusBerkas::Baru)->required(),
                DatePicker::make('tanggal_masuk')->label('Tanggal masuk')->default(now())->required(),
                TextInput::make('nilai_transaksi')->label('Nilai transaksi/objek')->numeric()->prefix('Rp'),
                Toggle::make('sangat_rahasia')->label('Sangat rahasia')->helperText('Hanya notaris, PIC, dan anggota berkas yang dapat membuka. Tidak dikirim ke AI.'),
                Toggle::make('cuma_cuma')->label('Jasa cuma-cuma (Pasal 37 UUJN)'),
                Select::make('anggota')->label('Anggota berkas')->relationship('anggota', 'name')->multiple()->preload(),
                Textarea::make('catatan')->label('Catatan internal')->rows(2)->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('nomor')->label('Nomor berkas')->weight('bold')->copyable(),
                TextEntry::make('judul')->label('Judul')->columnSpan(2),
                TextEntry::make('status')->label('Tahap')->badge(),
                TextEntry::make('jenisLayanan.nama')->label('Jenis layanan'),
                TextEntry::make('jabatan.nama_lengkap')->label('Notaris'),
                TextEntry::make('klien.nama')->label('Klien')->placeholder('—')
                    ->url(fn (Berkas $r) => $r->klien_id ? SubjekResource::getUrl('view', ['record' => $r->klien_id]) : null),
                TextEntry::make('pic.name')->label('PIC')->placeholder('—'),
                TextEntry::make('tanggal_masuk')->label('Masuk')->date('d/m/Y'),
                TextEntry::make('nilai_transaksi')->label('Nilai')->formatStateUsing(fn ($state) => $state ? Money::format($state) : null)->placeholder('—'),
                TextEntry::make('dokumen_kurang')->label('Dokumen kurang')->state(fn (Berkas $r) => $r->dokumenKurang())
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'success'),
                TextEntry::make('saldo_titipan')->label('Saldo dana titipan')->state(fn (Berkas $r) => Money::format($r->saldoTitipan())),
                IconEntry::make('sangat_rahasia')->label('Sangat rahasia')->boolean(),
                IconEntry::make('cuma_cuma')->label('Cuma-cuma')->boolean(),
                TextEntry::make('catatan')->label('Catatan')->columnSpanFull()->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('nomor')->label('Nomor')->searchable()->sortable(),
                TextColumn::make('judul')->label('Judul')->searchable()->wrap()->weight('bold')
                    ->description(fn (Berkas $r) => $r->klien?->nama),
                TextColumn::make('jenisLayanan.nama')->label('Layanan')->toggleable(),
                TextColumn::make('status')->label('Tahap')->badge(),
                TextColumn::make('pic.name')->label('PIC')->placeholder('—')->toggleable(),
                TextColumn::make('tanggal_masuk')->label('Masuk')->date('d/m/Y')->sortable(),
                TextColumn::make('updated_at')->label('Aktivitas terakhir')->since()->sortable()->toggleable(),
                IconColumn::make('sangat_rahasia')->label('Rahasia')->boolean()->trueIcon(Heroicon::OutlinedLockClosed)->falseIcon('')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Tahap')->options(StatusBerkas::class)->multiple()
                    ->default(array_map(fn ($s) => $s->value, StatusBerkas::aktif())),
                SelectFilter::make('jenis_layanan_id')->label('Layanan')->options(fn () => JenisLayanan::options()),
                SelectFilter::make('pic_id')->label('PIC')->options(fn () => static::userOptions()),
                TernaryFilter::make('macet')->label('Macet > 7 hari')
                    ->queries(
                        true: fn (Builder $query) => $query->whereIn('status', array_map(fn ($s) => $s->value, StatusBerkas::aktif()))->where('updated_at', '<', now()->subDays(7)),
                        false: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PihakRelationManager::class,
            RelationManagers\ChecklistRelationManager::class,
            RelationManagers\DokumenRelationManager::class,
            RelationManagers\AktaRelationManager::class,
            RelationManagers\TugasRelationManager::class,
            RelationManagers\TandaTerimaRelationManager::class,
            RelationManagers\TagihanRelationManager::class,
            RelationManagers\DanaTitipanRelationManager::class,
            RelationManagers\TenggatRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBerkas::route('/'),
            'create' => CreateBerkas::route('/create'),
            'view' => ViewBerkas::route('/{record}'),
            'edit' => EditBerkas::route('/{record}/edit'),
        ];
    }
}
