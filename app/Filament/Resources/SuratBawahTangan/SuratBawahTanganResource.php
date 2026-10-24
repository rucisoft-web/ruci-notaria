<?php

namespace App\Filament\Resources\SuratBawahTangan;

use App\Enums\JenisSurat;
use App\Filament\Concerns\WithRole;
use App\Filament\Resources\SuratBawahTangan\Pages\CreateSuratBawahTangan;
use App\Filament\Resources\SuratBawahTangan\Pages\EditSuratBawahTangan;
use App\Filament\Resources\SuratBawahTangan\Pages\ListSuratBawahTangan;
use App\Filament\Resources\SuratBawahTangan\Pages\ViewSuratBawahTangan;
use App\Models\Berkas;
use App\Models\BukuFisik;
use App\Models\Jabatan;
use App\Models\SuratBawahTangan;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Legalisasi, waarmerking, dan pengesahan fotokopi (Pasal 15 ayat 2 UUJN). */
class SuratBawahTanganResource extends Resource
{
    use WithRole;

    protected static ?string $model = SuratBawahTangan::class;

    protected static ?string $slug = 'legalisasi-waarmerking';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Kerja Harian';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'legalisasi/waarmerking';

    protected static ?string $pluralModelLabel = 'Legalisasi & Waarmerking';

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
        return static::canWork() && ! $record->batal;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $baru = fn (?SuratBawahTangan $record) => ! $record?->exists;

        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()
                ->description('Nomor diberikan otomatis saat disimpan, berurutan per notaris, per jenis, per tahun.')
                ->schema([
                    Select::make('jenis')->label('Jenis')->options(JenisSurat::class)->required()->live()
                        ->default(fn () => request()->query('jenis', JenisSurat::Legalisasi->value))->disabled(fn ($record) => ! $baru($record)),
                    Select::make('jabatan_id')->label('Notaris')->options(fn () => Jabatan::options())->required()
                        ->default(fn () => Jabatan::utama()?->id)->disabled(fn ($record) => ! $baru($record)),
                    DatePicker::make('tanggal')->label(fn (Get $get) => static::jenis($get) === 'waarmerking' ? 'Tanggal dibukukan' : 'Tanggal')
                        ->default(now())->required()->maxDate(now())->disabled(fn ($record) => ! $baru($record)),
                    DatePicker::make('tanggal_surat')->label('Tanggal surat')->visible(fn (Get $get) => static::jenis($get) === 'waarmerking')
                        ->required(fn (Get $get) => static::jenis($get) === 'waarmerking'),
                    TextInput::make('judul')->label(fn (Get $get) => static::jenis($get) === 'fotokopi' ? 'Jenis dokumen' : 'Judul/sifat surat')->required()->columnSpan(2),
                    Textarea::make('para_pihak')->label(fn (Get $get) => static::jenis($get) === 'fotokopi' ? 'Nama pemilik dokumen' : 'Para pihak (nama lengkap; untuk legalisasi: yang menandatangani di hadapan notaris)')
                        ->required()->rows(2)->columnSpan(2),
                    TextInput::make('pemohon')->label('Pemohon'),
                    TextInput::make('jumlah_lembar')->label('Jumlah lembar')->numeric()->minValue(1)->default(1),
                    Select::make('berkas_id')->label('Berkas (opsional)')->searchable()
                        ->getSearchResultsUsing(fn (string $s) => Berkas::query()->terlihatOleh(auth()->user())
                            ->where(fn ($q) => $q->where('nomor', 'like', "%{$s}%")->orWhere('judul', 'like', "%{$s}%"))->limit(20)->pluck('judul', 'id'))
                        ->getOptionLabelUsing(fn ($v) => Berkas::find($v)?->judul),
                    Select::make('buku_fisik_id')->label('Buku daftar fisik')
                        ->options(fn () => BukuFisik::query()->whereIn('jenis_daftar', ['legalisasi', 'waarmerking'])->get()->mapWithKeys(fn ($b) => [$b->id => $b->label()])),
                    TextInput::make('halaman_buku')->label('Halaman buku')->numeric(),
                    FileUpload::make('scan_path')->label('Pindaian surat (opsional)')->disk('local')->directory('surat-bawah-tangan')->visibility('private')
                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->maxSize(config('notaria.uploads.max_kb'))->columnSpanFull(),
                ]),
        ]);
    }

    private static function jenis(Get $get): ?string
    {
        $j = $get('jenis');

        return $j instanceof JenisSurat ? $j->value : $j;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('nomor')->label('Nomor')->searchable()->weight('bold'),
                TextColumn::make('jenis')->label('Jenis')->badge(),
                TextColumn::make('tanggal')->label('Tanggal')->date('d/m/Y')->sortable(),
                TextColumn::make('judul')->label('Surat')->searchable()->wrap(),
                TextColumn::make('para_pihak')->label('Para pihak')->searchable()->wrap()->limit(80),
                IconColumn::make('batal')->label('Batal')->boolean()->trueColor('danger')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('jenis')->label('Jenis')->options(JenisSurat::class),
                SelectFilter::make('jabatan_id')->label('Notaris')->options(fn () => Jabatan::options()),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('cetak')->label('Teks pengesahan')->icon(Heroicon::OutlinedPrinter)->color('gray')
                    ->url(fn (SuratBawahTangan $r) => route('cetak.pengesahan', $r))->openUrlInNewTab(),
                Action::make('batal')->label('Tandai batal')->icon(Heroicon::OutlinedXMark)->color('danger')
                    ->visible(fn (SuratBawahTangan $r) => ! $r->batal && static::isNotaris())->requiresConfirmation()
                    ->schema([Textarea::make('alasan_batal')->label('Alasan')->required()])
                    ->action(fn (SuratBawahTangan $r, array $data) => $r->update(['batal' => true, 'alasan_batal' => $data['alasan_batal']])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSuratBawahTangan::route('/'),
            'create' => CreateSuratBawahTangan::route('/create'),
            'view' => ViewSuratBawahTangan::route('/{record}'),
            'edit' => EditSuratBawahTangan::route('/{record}/edit'),
        ];
    }
}
