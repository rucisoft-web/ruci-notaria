<?php

namespace App\Filament\Resources\TemplateAkta;

use App\Enums\StatusTemplate;
use App\Filament\Concerns\WithRole;
use App\Filament\Resources\TemplateAkta\Pages\ManageTemplateAkta;
use App\Models\JenisLayanan;
use App\Models\TemplateAkta;
use App\Services\PenyusunDraf;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

/**
 * Template akta .docx. Staf menyiapkan, hanya notaris yang menyetujui. Template yang sudah disetujui tidak diubah:
 * perubahan dibuat sebagai versi baru, versi lama menjadi usang.
 */
class TemplateAktaResource extends Resource
{
    use WithRole;

    protected static ?string $model = TemplateAkta::class;

    protected static ?string $slug = 'template-akta';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'template akta';

    protected static ?string $pluralModelLabel = 'Template Akta';

    public static function canViewAny(): bool
    {
        return static::canWork() || static::isTamu();
    }

    public static function canCreate(): bool
    {
        return static::canWork();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canWork() && $record->status === StatusTemplate::Draf;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')->label('Nama template')->required()->maxLength(255),
            Select::make('jenis_layanan_id')->label('Jenis layanan')->options(fn () => JenisLayanan::query()->orderBy('nama')->pluck('nama', 'id'))->searchable(),
            FileUpload::make('path')->label('Berkas .docx')->required()->disk('local')->directory('template')->visibility('private')
                ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                ->maxSize(config('notaria.uploads.max_kb'))->columnSpanFull()
                ->helperText('Tulis variabel dengan format ${nama_variabel}. Daftar variabel ada di tombol "Daftar variabel".'),
            Textarea::make('catatan')->label('Catatan')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('nama')
            ->columns([
                TextColumn::make('nama')->label('Nama')->searchable(),
                TextColumn::make('jenisLayanan.nama')->label('Jenis layanan')->placeholder('Umum'),
                TextColumn::make('versi')->label('Versi'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('disetujuiOleh.name')->label('Disetujui')->description(fn (TemplateAkta $r) => $r->disetujui_at?->format('d/m/Y'))->placeholder('-'),
            ])
            ->filters([SelectFilter::make('status')->label('Status')->options(StatusTemplate::options())])
            ->recordActions([
                Action::make('unduh')->label('Unduh')->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (TemplateAkta $r) => Storage::disk('local')->download($r->path, str($r->nama)->slug().'-v'.$r->versi.'.docx')),
                EditAction::make(),
                Action::make('setujui')->label('Setujui')->icon(Heroicon::OutlinedCheckBadge)->color('success')
                    ->visible(fn (TemplateAkta $r) => static::isNotaris() && $r->status === StatusTemplate::Draf)
                    ->requiresConfirmation()
                    ->modalDescription('Template yang disetujui dapat dipakai staf membuat draf akta. Versi lain dengan nama yang sama menjadi usang.')
                    ->action(fn (TemplateAkta $r) => static::setujui($r)),
                Action::make('versiBaru')->label('Versi baru')->icon(Heroicon::OutlinedDocumentPlus)
                    ->visible(fn (TemplateAkta $r) => static::canWork() && $r->status !== StatusTemplate::Draf)
                    ->schema([
                        FileUpload::make('path')->label('Berkas .docx')->required()->disk('local')->directory('template')->visibility('private')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document']),
                        Textarea::make('catatan')->label('Perubahan')->rows(2),
                    ])
                    ->action(function (TemplateAkta $r, array $data) {
                        $baru = TemplateAkta::create([
                            'nama' => $r->nama, 'jenis_layanan_id' => $r->jenis_layanan_id, 'path' => $data['path'],
                            'versi' => (int) TemplateAkta::where('nama', $r->nama)->max('versi') + 1,
                            'status' => StatusTemplate::Draf, 'catatan' => $data['catatan'] ?? null,
                        ]);
                        Notification::make()->success()->title('Versi '.$baru->versi.' dibuat, menunggu persetujuan notaris.')->send();
                    }),
                Action::make('usangkan')->label('Usangkan')->icon(Heroicon::OutlinedArchiveBox)->color('danger')
                    ->visible(fn (TemplateAkta $r) => static::isNotaris() && $r->status === StatusTemplate::Disetujui)
                    ->requiresConfirmation()
                    ->action(fn (TemplateAkta $r) => $r->update(['status' => StatusTemplate::Usang])),
            ]);
    }

    public static function setujui(TemplateAkta $template): void
    {
        DB::transaction(function () use ($template) {
            TemplateAkta::where('nama', $template->nama)->whereKeyNot($template->id)
                ->where('status', StatusTemplate::Disetujui)->update(['status' => StatusTemplate::Usang]);
            $template->update(['status' => StatusTemplate::Disetujui, 'disetujui_oleh' => auth()->id(), 'disetujui_at' => now()]);
        });
        Notification::make()->success()->title('Template disetujui.')->send();
    }

    /** Ringkasan variabel untuk modal bantuan. */
    public static function daftarVariabel(): array
    {
        return [Section::make()->schema(collect(PenyusunDraf::VARIABEL)
            ->map(fn ($ket, $nama) => Text::make(str_starts_with($nama, '${') ? $nama.': '.$ket : '${'.$nama.'}: '.$ket))
            ->values()->all())];
    }

    public static function getPages(): array
    {
        return ['index' => ManageTemplateAkta::route('/')];
    }
}
