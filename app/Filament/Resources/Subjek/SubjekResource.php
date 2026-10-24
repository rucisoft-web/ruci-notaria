<?php

namespace App\Filament\Resources\Subjek;

use App\Filament\Concerns\WithRole;
use App\Filament\Resources\Subjek\Pages\CreateSubjek;
use App\Filament\Resources\Subjek\Pages\EditSubjek;
use App\Filament\Resources\Subjek\Pages\ListSubjek;
use App\Filament\Resources\Subjek\Pages\ViewSubjek;
use App\Filament\Resources\Subjek\RelationManagers\DokumenRelationManager;
use App\Filament\Resources\Subjek\RelationManagers\RelasiRelationManager;
use App\Models\Subjek;
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
use Filament\Schemas\Components\Utilities\Get;
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

/** Klien, penghadap, saksi, dan badan. */
class SubjekResource extends Resource
{
    use WithRole;

    protected static ?string $model = Subjek::class;

    protected static ?string $slug = 'subjek';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Kerja Harian';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'klien/penghadap';

    protected static ?string $pluralModelLabel = 'Klien & Penghadap';

    protected static ?string $recordTitleAttribute = 'nama';

    public const STATUS_KAWIN = ['belum_kawin' => 'Belum kawin', 'kawin' => 'Kawin', 'cerai_hidup' => 'Cerai hidup', 'cerai_mati' => 'Cerai mati'];

    public const BENTUK_BADAN = ['pt' => 'Perseroan Terbatas', 'yayasan' => 'Yayasan', 'perkumpulan' => 'Perkumpulan', 'koperasi' => 'Koperasi',
        'cv' => 'CV', 'firma' => 'Firma', 'instansi' => 'Instansi pemerintah', 'bank' => 'Bank', 'lainnya' => 'Lainnya'];

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
        return static::isNotaris() && ! $record->aktaPenghadap()->exists();
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $orang = fn (Get $get) => $get('jenis') !== 'badan';

        return $schema->components([
            Section::make('Identitas')->columns(4)->columnSpanFull()->schema([
                Select::make('jenis')->label('Jenis')->options(['orang' => 'Orang perseorangan', 'badan' => 'Badan'])->default('orang')->required()->live(),
                TextInput::make('nama')->label(fn (Get $get) => $get('jenis') === 'badan' ? 'Nama badan' : 'Nama sesuai identitas')->required()->maxLength(255)->columnSpan(3),
                Select::make('jenis_identitas')->label('Jenis identitas')->options(['ktp' => 'KTP', 'paspor' => 'Paspor', 'lainnya' => 'Lainnya'])->default('ktp')->visible($orang),
                TextInput::make('nik')->label(fn (Get $get) => $get('jenis_identitas') === 'paspor' ? 'Nomor paspor' : 'NIK')->maxLength(30)->visible($orang)
                    ->rule(fn (Get $get) => $get('jenis_identitas') === 'ktp' ? 'digits:16' : null)
                    ->helperText('Disimpan terenkripsi.'),
                DatePicker::make('identitas_berlaku_sampai')->label('Berlaku sampai')->helperText('Kosongkan bila seumur hidup.')->visible($orang),
                Select::make('jenis_kelamin')->label('Jenis kelamin')->options(['L' => 'Laki-laki', 'P' => 'Perempuan'])->visible($orang),
                TextInput::make('tempat_lahir')->label('Tempat lahir')->visible($orang),
                DatePicker::make('tanggal_lahir')->label('Tanggal lahir')->visible($orang),
                TextInput::make('kewarganegaraan')->label('Kewarganegaraan')->default('Indonesia')->visible($orang),
                Select::make('status_perkawinan')->label('Status perkawinan')->options(self::STATUS_KAWIN)->visible($orang),
                TextInput::make('pekerjaan')->label('Pekerjaan')->visible($orang)->columnSpan(2),
                Select::make('bentuk_badan')->label('Bentuk badan')->options(self::BENTUK_BADAN)->visible(fn (Get $get) => ! $orang($get)),
                TextInput::make('nomor_sk_badan')->label('Nomor SK pengesahan')->visible(fn (Get $get) => ! $orang($get)),
                TextInput::make('nib')->label('NIB')->visible(fn (Get $get) => ! $orang($get)),
                TextInput::make('npwp')->label('NPWP')->maxLength(30),
            ]),
            Section::make('Alamat dan kontak')->columns(4)->columnSpanFull()->schema([
                Textarea::make('alamat')->label('Alamat')->rows(2)->columnSpan(2),
                TextInput::make('rt_rw')->label('RT/RW')->placeholder('001/002'),
                TextInput::make('kelurahan')->label('Kelurahan/desa'),
                TextInput::make('kecamatan')->label('Kecamatan'),
                TextInput::make('kota')->label('Kabupaten/kota'),
                TextInput::make('provinsi')->label('Provinsi'),
                TextInput::make('telepon')->label('Telepon/WhatsApp')->tel(),
                TextInput::make('email')->label('Email')->email(),
            ]),
            Section::make('Lainnya')->columns(4)->columnSpanFull()->schema([
                Toggle::make('saksi_kantor')->label('Staf yang biasa menjadi saksi akta'),
                Toggle::make('persetujuan_ai')->label('Memberi persetujuan pemrosesan data oleh AI')
                    ->helperText('Wajib sebelum data orang ini dikirim ke penyedia AI.'),
                Textarea::make('catatan')->label('Catatan')->rows(2)->columnSpan(2),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(4)->columnSpanFull()->schema([
                TextEntry::make('nama')->label('Nama')->weight('bold')->columnSpan(2),
                TextEntry::make('jenis')->label('Jenis')->badge()->formatStateUsing(fn ($state) => $state === 'badan' ? 'Badan' : 'Orang'),
                TextEntry::make('nik')->label('NIK/paspor')->placeholder('—')->copyable(),
                TextEntry::make('tempat_tanggal_lahir')->label('Tempat, tanggal lahir')->placeholder('—')
                    ->state(fn (Subjek $r) => $r->tanggal_lahir ? trim(($r->tempat_lahir ?? '').', '.$r->tanggal_lahir->translatedFormat('d F Y'), ', ') : null),
                TextEntry::make('umur')->label('Umur')->state(fn (Subjek $r) => $r->umurPada(now()) !== null ? $r->umurPada(now()).' tahun' : null)->placeholder('—'),
                TextEntry::make('status_perkawinan')->label('Status perkawinan')->formatStateUsing(fn ($state) => self::STATUS_KAWIN[$state] ?? $state)->placeholder('—'),
                TextEntry::make('pekerjaan')->label('Pekerjaan')->placeholder('—'),
                TextEntry::make('kewarganegaraan')->label('Kewarganegaraan'),
                TextEntry::make('identitas_berlaku_sampai')->label('Identitas berlaku sampai')->date('d/m/Y')->placeholder('Seumur hidup'),
                TextEntry::make('alamat_lengkap')->label('Alamat')->state(fn (Subjek $r) => $r->alamatLengkap())->columnSpan(2)->placeholder('—'),
                TextEntry::make('telepon')->label('Telepon')->placeholder('—'),
                TextEntry::make('email')->label('Email')->placeholder('—'),
                TextEntry::make('npwp')->label('NPWP')->placeholder('—'),
                IconEntry::make('saksi_kantor')->label('Saksi kantor')->boolean(),
                IconEntry::make('persetujuan_ai')->label('Persetujuan AI')->boolean(),
                TextEntry::make('catatan')->label('Catatan')->columnSpanFull()->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('nama')
            ->columns([
                TextColumn::make('nama')->label('Nama')->weight('bold')->wrap()
                    ->searchable(query: fn (Builder $query, string $search) => $query->cari($search))
                    ->description(fn (Subjek $r) => $r->isBadan() ? (self::BENTUK_BADAN[$r->bentuk_badan] ?? 'Badan') : $r->pekerjaan),
                TextColumn::make('nik_samar')->label('NIK')->state(fn (Subjek $r) => $r->nikTersamar())->placeholder('—'),
                TextColumn::make('tanggal_lahir')->label('Tgl lahir')->date('d/m/Y')->placeholder('—')->toggleable(),
                TextColumn::make('kota')->label('Kota')->placeholder('—')->toggleable(),
                TextColumn::make('telepon')->label('Telepon')->placeholder('—')->toggleable(),
                TextColumn::make('akta_penghadap_count')->label('Akta')->counts('aktaPenghadap')->sortable(),
                IconColumn::make('saksi_kantor')->label('Saksi')->boolean()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('jenis')->label('Jenis')->options(['orang' => 'Orang', 'badan' => 'Badan']),
                TernaryFilter::make('saksi_kantor')->label('Saksi kantor'),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [RelasiRelationManager::class, DokumenRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubjek::route('/'),
            'create' => CreateSubjek::route('/create'),
            'view' => ViewSubjek::route('/{record}'),
            'edit' => EditSubjek::route('/{record}/edit'),
        ];
    }
}
