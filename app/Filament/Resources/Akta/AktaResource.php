<?php

namespace App\Filament\Resources\Akta;

use App\Enums\KategoriAkta;
use App\Enums\SifatAkta;
use App\Enums\StatusAkta;
use App\Filament\Concerns\WithRole;
use App\Filament\Resources\Akta\Pages\EditAkta;
use App\Filament\Resources\Akta\Pages\ListAkta;
use App\Filament\Resources\Akta\Pages\ViewAkta;
use App\Filament\Resources\Berkas\BerkasResource;
use App\Models\Akta;
use App\Models\BukuFisik;
use App\Models\Jabatan;
use App\Models\JenisLayanan;
use App\Support\Money;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Register akta. Akta dibuat dari halaman berkas; di sini dicari, dijadwalkan, ditandatangani, dan diberi nomor. */
class AktaResource extends Resource
{
    use WithRole;

    protected static ?string $model = Akta::class;

    protected static ?string $slug = 'akta';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Kerja Harian';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'akta';

    protected static ?string $pluralModelLabel = 'Akta';

    protected static ?string $recordTitleAttribute = 'judul';

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
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canWork() || ($record->isBernomor() && static::canArchive());
    }

    public static function canDelete(Model $record): bool
    {
        return static::isNotaris() && ! $record->isBernomor();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $terkunci = fn (?Akta $record) => (bool) $record?->isBernomor();

        return $schema->components([
            Section::make('Akta')->columns(4)->columnSpanFull()
                ->description(fn (?Akta $record) => $record?->isBernomor() ? 'Akta sudah bernomor: judul, sifat, dan kategori terkunci. Kesalahan ketik pada minuta dibetulkan dengan berita acara pembetulan.' : null)
                ->schema([
                    TextInput::make('judul')->label('Judul akta')->required()->columnSpan(2)->disabled($terkunci),
                    Select::make('jenis_layanan_id')->label('Jenis')->options(fn () => JenisLayanan::options())->disabled($terkunci),
                    Select::make('sifat')->label('Sifat')->options(SifatAkta::class)->required()->disabled($terkunci),
                    Select::make('kategori')->label('Kategori')->options(KategoriAkta::class)->required()->disabled($terkunci),
                    TextInput::make('nilai_ekonomis')->label('Nilai ekonomis')->numeric()->prefix('Rp'),
                    TextInput::make('kota_tanda_tangan')->label('Kota penandatanganan'),
                    TextInput::make('provinsi_tanda_tangan')->label('Provinsi penandatanganan'),
                ]),
            Section::make('Minuta dan buku daftar')->columns(4)->columnSpanFull()->visible($terkunci)->schema([
                Toggle::make('minuta_lampiran_lengkap')->label('Lampiran minuta lengkap'),
                Toggle::make('minuta_sidik_jari_lengkap')->label('Sidik jari penghadap lengkap'),
                Select::make('buku_fisik_id')->label('Buku repertorium fisik')
                    ->options(fn () => BukuFisik::query()->where('jenis_daftar', 'repertorium')->get()->mapWithKeys(fn ($b) => [$b->id => $b->label()])),
                TextInput::make('halaman_buku')->label('Halaman buku')->numeric()->minValue(1),
                FileUpload::make('minuta_scan_path')->label('Pindaian minuta (PDF, cadangan; bukan pengganti minuta)')->disk('local')->directory('minuta')
                    ->visibility('private')->acceptedFileTypes(['application/pdf'])->maxSize(config('notaria.uploads.max_kb'))->columnSpanFull(),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('nomor_tampil')->label('Nomor akta')->weight('bold')->size('lg')
                    ->state(fn (Akta $r) => $r->isBernomor() ? 'No. '.$r->nomor_bulanan.' · urut '.$r->nomor_urut : 'Belum bernomor'),
                TextEntry::make('judul')->label('Judul')->columnSpan(2)->weight('bold'),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('berkas.nomor')->label('Berkas')->url(fn (Akta $r) => BerkasResource::getUrl('view', ['record' => $r->berkas_id])),
                TextEntry::make('jabatan.nama_lengkap')->label('Notaris'),
                TextEntry::make('sifat')->label('Sifat')->badge(),
                TextEntry::make('kategori')->label('Kategori')->badge(),
                TextEntry::make('tanggal_jam')->label('Tanggal & jam akta')->placeholder('—')
                    ->state(fn (Akta $r) => $r->tanggal ? $r->tanggal->translatedFormat('l, d F Y').' pukul '.substr((string) $r->jam, 0, 5) : null),
                TextEntry::make('jadwal_at')->label('Jadwal')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextEntry::make('tempat')->label('Tempat')->placeholder('—'),
                TextEntry::make('nilai_ekonomis')->label('Nilai ekonomis')->formatStateUsing(fn ($state) => $state !== null ? Money::format($state) : null)->placeholder('—'),
                IconEntry::make('dibacakan')->label('Dibacakan')->boolean()->visible(fn (Akta $r) => $r->isBernomor()),
                TextEntry::make('renvoi')->label('Renvoi (tambah/coret/ganti)')->visible(fn (Akta $r) => $r->isBernomor())
                    ->state(fn (Akta $r) => $r->renvoi_tambah.' / '.$r->renvoi_coret.' / '.$r->renvoi_ganti),
                TextEntry::make('dinomoriOleh.name')->label('Dinomori oleh')->placeholder('—')->visible(fn (Akta $r) => $r->isBernomor()),
                TextEntry::make('jilid_label')->label('Jilid minuta')->state(fn (Akta $r) => $r->jilid?->label())->placeholder('Belum dijilid')->visible(fn (Akta $r) => $r->isBernomor()),
                TextEntry::make('halaman_buku')->label('Halaman repertorium fisik')->placeholder('—')->visible(fn (Akta $r) => $r->isBernomor()),
                IconEntry::make('minuta_lampiran_lengkap')->label('Lampiran lengkap')->boolean()->visible(fn (Akta $r) => $r->isBernomor()),
                IconEntry::make('minuta_sidik_jari_lengkap')->label('Sidik jari lengkap')->boolean()->visible(fn (Akta $r) => $r->isBernomor()),
                TextEntry::make('catatan_penandatanganan')->label('Catatan penandatanganan')->columnSpanFull()->placeholder('—'),
                TextEntry::make('alasan_batal')->label('Alasan batal')->columnSpanFull()->visible(fn (Akta $r) => $r->status === StatusAkta::Batal),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('nomor_bulanan')->label('No.')->sortable()->placeholder('—'),
                TextColumn::make('nomor_urut')->label('Urut')->sortable()->placeholder('—')->toggleable(),
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y')->sortable()->placeholder('—'),
                TextColumn::make('judul')->label('Judul')->searchable()->wrap()->weight('bold')
                    ->description(fn (Akta $r) => $r->penghadap->map(fn ($p) => $p->subjek->nama)->implode(', ')),
                TextColumn::make('berkas.nomor')->label('Berkas')->searchable()->toggleable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('jadwal_at')->label('Jadwal')->dateTime('d/m/Y H:i')->sortable()->placeholder('—')->toggleable(),
            ])
            ->modifyQueryUsing(fn (Builder $query) => $query->with('penghadap.subjek', 'berkas'))
            ->filters([
                SelectFilter::make('status')->label('Status')->options(StatusAkta::class)->multiple(),
                SelectFilter::make('jabatan_id')->label('Notaris')->options(fn () => Jabatan::options()),
                SelectFilter::make('kategori')->label('Kategori')->options(KategoriAkta::class),
                Filter::make('penghadap')->label('Nama penghadap')
                    ->schema([TextInput::make('nama')->label('Nama penghadap')])
                    ->query(fn (Builder $query, array $data) => filled($data['nama'] ?? null)
                        ? $query->whereHas('penghadap.subjek', fn ($s) => $s->where('nama', 'like', '%'.$data['nama'].'%')) : $query),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PenghadapRelationManager::class,
            RelationManagers\SaksiRelationManager::class,
            RelationManagers\DrafRelationManager::class,
            RelationManagers\SalinanRelationManager::class,
            RelationManagers\PembetulanRelationManager::class,
            RelationManagers\PeminjamanRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAkta::route('/'),
            'view' => ViewAkta::route('/{record}'),
            'edit' => EditAkta::route('/{record}/edit'),
        ];
    }
}
