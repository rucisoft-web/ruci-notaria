<?php

namespace App\Filament\Resources\Akta;

use App\Enums\StatusAkta;
use App\Enums\StatusBerkas;
use App\Enums\StatusTemplate;
use App\Models\Akta;
use App\Models\TemplateAkta;
use App\Services\KesiapanAkta;
use App\Services\PemeriksaDraf;
use App\Services\PenomoranAkta;
use App\Services\PenyusunDraf;
use App\Support\Docx;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Text;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

/** Tombol alur akta: jadwalkan, periksa kesiapan, catat penandatanganan & beri nomor, draf, batal. */
class AktaActions
{
    public static function all(): array
    {
        return [
            static::buatDraf(), static::unggahDraf(), static::jadwalkan(), static::periksaKesiapan(),
            static::beriNomor(), static::lembarTandaTangan(), static::batalkan(),
        ];
    }

    private static function bisaKerja(): bool
    {
        return (bool) auth()->user()?->role?->canWork();
    }

    public static function temuanHtml(array $temuan): HtmlString
    {
        if ($temuan === []) {
            return new HtmlString('<p class="nt-ok">Tidak ada temuan. Akta siap ditandatangani.</p>');
        }
        $html = '<ul class="nt-temuan">';
        foreach ($temuan as $t) {
            $label = ['kritis' => 'Kritis', 'perlu_dicek' => 'Perlu dicek', 'saran' => 'Saran'][$t['tingkat']] ?? $t['tingkat'];
            $html .= '<li class="'.e($t['tingkat']).'"><b>'.e($label).'</b> <small>'.e($t['kode']).'</small> '.e($t['pesan']).'</li>';
        }

        return new HtmlString($html.'</ul>');
    }

    public static function jadwalkan(): Action
    {
        return Action::make('jadwalkan')->label('Jadwalkan')->icon(Heroicon::OutlinedCalendarDays)->color('info')
            ->visible(fn (Akta $record) => static::bisaKerja() && ! $record->isBernomor() && $record->status !== StatusAkta::Batal)
            ->fillForm(fn (Akta $record) => [
                'jadwal_at' => $record->jadwal_at ?? now()->addDay()->setTime(10, 0),
                'tempat' => $record->tempat ?? 'Kantor Notaris',
                'kota_tanda_tangan' => $record->kota_tanda_tangan ?? $record->jabatan?->tempat_kedudukan,
                'provinsi_tanda_tangan' => $record->provinsi_tanda_tangan ?? $record->jabatan?->provinsi,
            ])
            ->schema([
                DateTimePicker::make('jadwal_at')->label('Tanggal & jam')->seconds(false)->required(),
                TextInput::make('tempat')->label('Tempat')->required(),
                TextInput::make('kota_tanda_tangan')->label('Kota')->required(),
                TextInput::make('provinsi_tanda_tangan')->label('Provinsi')->required()
                    ->helperText('Diperiksa terhadap wilayah jabatan notaris (Pasal 17 UUJN).'),
            ])
            ->action(function (Akta $record, array $data) {
                $record->update($data + ['status' => StatusAkta::Dijadwalkan]);
                if (in_array($record->berkas->status, [StatusBerkas::Baru, StatusBerkas::Dokumen, StatusBerkas::Draf, StatusBerkas::Persetujuan], true)) {
                    $record->berkas->update(['status' => StatusBerkas::Dijadwalkan]);
                }
                $temuan = app(KesiapanAkta::class)->periksa($record->fresh());
                Notification::make()->title('Akta dijadwalkan '.Carbon::parse($data['jadwal_at'])->translatedFormat('l, d F Y H:i'))
                    ->body(KesiapanAkta::adaKritis($temuan) ? 'Masih ada temuan kritis; buka "Periksa kesiapan".' : null)
                    ->{KesiapanAkta::adaKritis($temuan) ? 'warning' : 'success'}()->send();
            });
    }

    public static function periksaKesiapan(): Action
    {
        return Action::make('periksa_kesiapan')->label('Periksa kesiapan')->icon(Heroicon::OutlinedClipboardDocumentCheck)->color('gray')
            ->visible(fn (Akta $record) => ! $record->isBernomor() && $record->status !== StatusAkta::Batal)
            ->modalHeading('Pemeriksaan kesiapan akta')
            ->modalSubmitAction(false)->modalCancelActionLabel('Tutup')
            ->schema(fn (Akta $record) => [Text::make(static::temuanHtml(app(KesiapanAkta::class)->periksa($record)))]);
    }

    public static function beriNomor(): Action
    {
        return Action::make('beri_nomor')->label('Catat penandatanganan & beri nomor')->icon(Heroicon::OutlinedHashtag)->color('success')
            ->visible(fn (Akta $record) => auth()->user()?->role?->isNotaris() && ! $record->isBernomor() && $record->status !== StatusAkta::Batal)
            ->modalHeading('Catat penandatanganan dan beri nomor akta')
            ->modalDescription('Nomor diberikan berurutan dan tidak dapat diubah atau dihapus. Pastikan akta benar-benar sudah dibacakan dan ditandatangani.')
            ->modalSubmitActionLabel('Beri nomor')
            ->fillForm(fn (Akta $record) => [
                'tanggal' => ($record->jadwal_at && $record->jadwal_at->lte(now()) ? $record->jadwal_at : now())->toDateString(),
                'jam' => ($record->jadwal_at && $record->jadwal_at->lte(now()) ? $record->jadwal_at : now())->format('H:i'),
                'dibacakan' => true, 'renvoi_tambah' => 0, 'renvoi_coret' => 0, 'renvoi_ganti' => 0,
            ])
            ->schema(fn (Akta $record) => [
                Text::make(static::temuanHtml($temuan = app(KesiapanAkta::class)->periksa($record))),
                DatePicker::make('tanggal')->label('Tanggal akta')->required()->maxDate(now()),
                TimePicker::make('jam')->label('Jam akta')->seconds(false)->required(),
                Toggle::make('dibacakan')->label('Akta dibacakan')->helperText('Matikan bila penghadap menghendaki tidak dibacakan (Pasal 16 ayat 7): setiap halaman minuta wajib diparaf.'),
                TextInput::make('renvoi_tambah')->label('Renvoi: tambahan')->numeric()->minValue(0)->default(0),
                TextInput::make('renvoi_coret')->label('Renvoi: coretan')->numeric()->minValue(0)->default(0),
                TextInput::make('renvoi_ganti')->label('Renvoi: penggantian')->numeric()->minValue(0)->default(0),
                Textarea::make('catatan_penandatanganan')->label('Catatan (misal penghadap yang membubuhkan cap jempol dan alasannya)')->rows(2),
                Textarea::make('alasan_kesampingkan')->label('Alasan mengesampingkan temuan kritis')->rows(2)
                    ->visible(KesiapanAkta::adaKritis($temuan))->required(KesiapanAkta::adaKritis($temuan)),
            ])
            ->action(function (Akta $record, array $data, Action $action) {
                try {
                    $jam = Carbon::parse($data['jam'])->format('H:i');
                    $akta = app(PenomoranAkta::class)->beriNomor($record, Carbon::parse($data['tanggal']), $jam, auth()->user(), $data);
                } catch (ValidationException $e) {
                    Notification::make()->title('Nomor belum diberikan')->body(collect($e->errors())->flatten()->implode(' '))->danger()->send();
                    $action->halt();

                    return;
                }
                Notification::make()->title('Akta Nomor '.$akta->nomor_bulanan.' tanggal '.$akta->tanggal->translatedFormat('d F Y'))
                    ->body('Tercatat di repertorium dengan nomor urut '.$akta->nomor_urut.'. Buat ulang draf agar awal akta memuat nomor dan jam.')->success()->persistent()->send();
            });
    }

    public static function batalkan(): Action
    {
        return Action::make('batalkan')->label('Batalkan')->icon(Heroicon::OutlinedXMark)->color('danger')
            ->visible(fn (Akta $record) => static::bisaKerja() && ! $record->isBernomor() && $record->status !== StatusAkta::Batal)
            ->requiresConfirmation()
            ->schema([Textarea::make('alasan_batal')->label('Alasan')->required()])
            ->action(fn (Akta $record, array $data) => $record->update(['status' => StatusAkta::Batal, 'alasan_batal' => $data['alasan_batal']]));
    }

    public static function buatDraf(): Action
    {
        return Action::make('buat_draf')->label('Buat draf dari template')->icon(Heroicon::OutlinedDocumentPlus)->color('primary')
            ->visible(fn (Akta $record) => static::bisaKerja() && $record->status !== StatusAkta::Batal)
            ->schema(fn (Akta $record) => [
                Select::make('template_id')->label('Template (disetujui)')->required()
                    ->options(TemplateAkta::query()->where('status', StatusTemplate::Disetujui)
                        ->orderByRaw('CASE WHEN jenis_layanan_id = ? THEN 0 ELSE 1 END', [$record->jenis_layanan_id ?? 0])
                        ->orderBy('nama')->get()->mapWithKeys(fn ($t) => [$t->id => $t->nama.' v'.$t->versi]))
                    ->default($record->drafTerakhir?->template_akta_id),
            ])
            ->action(function (Akta $record, array $data) {
                try {
                    $draf = app(PenyusunDraf::class)->buat($record, TemplateAkta::findOrFail($data['template_id']));
                    app(PemeriksaDraf::class)->periksa($draf);
                } catch (\Throwable $e) {
                    Notification::make()->title('Draf gagal dibuat')->body($e->getMessage())->danger()->send();

                    return;
                }
                if ($record->berkas->status === StatusBerkas::Baru || $record->berkas->status === StatusBerkas::Dokumen) {
                    $record->berkas->update(['status' => StatusBerkas::Draf]);
                }
                Notification::make()->title('Draf versi '.$draf->versi.' dibuat')
                    ->body(count($draf->temuan ?? []) ? count($draf->temuan).' temuan pemeriksa; lihat tab Draf.' : 'Tidak ada temuan pemeriksa.')
                    ->success()->send();
            });
    }

    public static function unggahDraf(): Action
    {
        return Action::make('unggah_draf')->label('Unggah draf hasil suntingan')->icon(Heroicon::OutlinedArrowUpTray)->color('gray')
            ->visible(fn (Akta $record) => static::bisaKerja() && $record->status !== StatusAkta::Batal)
            ->schema([
                FileUpload::make('path')->label('Berkas .docx')->required()->disk('local')->directory('tmp-draf')->visibility('private')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->maxSize(config('notaria.uploads.max_kb')),
                Toggle::make('isi_garis')->label('Isi sisa baris dengan strip otomatis')->default(false),
                Textarea::make('catatan')->label('Catatan perubahan')->rows(2),
            ])
            ->action(function (Akta $record, array $data) {
                $disk = Storage::disk('local');
                $versi = (int) $record->draf()->max('versi') + 1;
                $tujuan = "draf/akta-{$record->id}/v{$versi}.docx";
                $disk->move($data['path'], $tujuan);
                if ($data['isi_garis'] ?? false) {
                    Docx::isiGaris($disk->path($tujuan));
                }
                $draf = $record->draf()->create(['versi' => $versi, 'path' => $tujuan, 'sumber' => 'unggahan', 'catatan' => $data['catatan'] ?? null, 'created_by' => auth()->id()]);
                try {
                    app(PemeriksaDraf::class)->periksa($draf);
                } catch (\Throwable $e) {
                    Notification::make()->title('Draf tersimpan, tetapi tidak dapat diperiksa')->body($e->getMessage())->warning()->send();

                    return;
                }
                Notification::make()->title('Draf versi '.$versi.' tersimpan dan diperiksa')->success()->send();
            });
    }

    public static function lembarTandaTangan(): Action
    {
        return Action::make('lembar_ttd')->label('Lembar penandatanganan')->icon(Heroicon::OutlinedPrinter)->color('gray')
            ->visible(fn (Akta $record) => $record->status !== StatusAkta::Batal)
            ->url(fn (Akta $record) => route('cetak.lembar-ttd', $record))->openUrlInNewTab();
    }
}
