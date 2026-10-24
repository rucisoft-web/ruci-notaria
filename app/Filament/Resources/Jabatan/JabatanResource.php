<?php

namespace App\Filament\Resources\Jabatan;

use App\Filament\Concerns\AdminPermissions;
use App\Filament\Resources\Jabatan\Pages\ManageJabatan;
use App\Models\Jabatan;
use App\Models\Subjek;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Profil jabatan notaris: SK, tempat kedudukan, wilayah jabatan, cuti dan notaris pengganti. */
class JabatanResource extends Resource
{
    use AdminPermissions;

    protected static ?string $model = Jabatan::class;

    protected static ?string $slug = 'jabatan';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'jabatan notaris';

    protected static ?string $pluralModelLabel = 'Jabatan Notaris';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Jabatan')->columns(3)->columnSpanFull()->schema([
                TextInput::make('nama')->label('Nama singkat')->required(),
                TextInput::make('nama_lengkap')->label('Nama lengkap dengan gelar (untuk awal akta)')->required()->columnSpan(2),
                Select::make('jenis')->label('Jenis')->options(['notaris' => 'Notaris', 'pengganti' => 'Notaris pengganti'])->default('notaris')->required(),
                TextInput::make('nomor_sk')->label('Nomor SK pengangkatan'),
                DatePicker::make('tanggal_sk')->label('Tanggal SK'),
                DatePicker::make('tanggal_sumpah')->label('Tanggal sumpah'),
                DatePicker::make('tanggal_lahir')->label('Tanggal lahir')->helperText('Untuk pengingat batas usia 65 tahun.'),
                TextInput::make('kode')->label('Kode nomor')->default('N')->maxLength(10)->helperText('Dipakai pada nomor legalisasi, misal 12/Leg/N/2026.'),
                TextInput::make('tempat_kedudukan')->label('Tempat kedudukan (kab/kota)')->required(),
                TextInput::make('provinsi')->label('Wilayah jabatan (provinsi)')->required(),
                Textarea::make('alamat_kantor')->label('Alamat kantor')->rows(2),
                Select::make('user_id')->label('Akun pengguna')->options(fn () => User::query()->orderBy('name')->pluck('name', 'id')),
                Select::make('subjek_id')->label('Data diri (untuk pemeriksaan Pasal 52)')->searchable()
                    ->getSearchResultsUsing(fn (string $s) => Subjek::query()->cari($s)->limit(20)->pluck('nama', 'id'))
                    ->getOptionLabelUsing(fn ($v) => Subjek::find($v)?->nama)
                    ->helperText('Catat suami/istri dan keluarga notaris sebagai hubungan pada data diri ini.'),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]),
            Section::make('PPAT')->columns(3)->columnSpanFull()->collapsed()->schema([
                Toggle::make('is_ppat')->label('Juga menjabat PPAT'),
                TextInput::make('daerah_kerja_ppat')->label('Daerah kerja PPAT'),
            ]),
            Section::make('Cuti')->columns(3)->columnSpanFull()->collapsed()->schema([
                DatePicker::make('cuti_mulai')->label('Cuti mulai'),
                DatePicker::make('cuti_selesai')->label('Cuti selesai'),
                Select::make('pengganti_id')->label('Notaris pengganti')->options(fn () => Jabatan::query()->where('jenis', 'pengganti')->pluck('nama_lengkap', 'id')),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_lengkap')->label('Nama')->weight('bold')->description(fn (Jabatan $r) => $r->jenis === 'pengganti' ? 'Notaris pengganti' : 'Notaris'),
                TextColumn::make('nomor_sk')->label('SK')->placeholder('—'),
                TextColumn::make('tempat_kedudukan')->label('Kedudukan'),
                TextColumn::make('provinsi')->label('Wilayah jabatan'),
                IconColumn::make('is_ppat')->label('PPAT')->boolean(),
                TextColumn::make('cuti')->label('Cuti')->state(fn (Jabatan $r) => $r->cuti_mulai ? $r->cuti_mulai->format('d/m/Y').' – '.$r->cuti_selesai?->format('d/m/Y') : null)->placeholder('—'),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageJabatan::route('/')];
    }
}
