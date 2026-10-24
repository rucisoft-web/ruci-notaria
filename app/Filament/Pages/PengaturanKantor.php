<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\WithRole;
use App\Models\AiPermintaan;
use App\Models\Pengaturan;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Pengaturan kantor dan AI. Semua fitur AI mati sampai notaris menyalakannya di sini. */
class PengaturanKantor extends Page
{
    use WithRole;

    protected static ?string $title = 'Pengaturan Kantor';

    protected static ?string $slug = 'pengaturan-kantor';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.pengaturan-kantor';

    /** Kunci pengaturan yang dapat diubah di layar ini. */
    private const KUNCI = ['kantor.nama', 'kantor.kota', 'kantor.telepon', 'kantor.zona_waktu', 'nomor.berkas', 'ai.aktif', 'ai.ekstraksi', 'ai.pemeriksa', 'ai.batas_bulanan_usd'];

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return static::canAdminister();
    }

    public function mount(): void
    {
        $isi = [];
        foreach (self::KUNCI as $k) {
            data_set($isi, $k, Pengaturan::get($k));
        }
        $this->form->fill($isi);
    }

    public function form(Schema $schema): Schema
    {
        $aiTerkunci = fn () => ! static::isNotaris();
        $tanpaKunci = blank(config('notaria.ai.api_key'));

        return $schema->statePath('data')->components([
            Section::make('Kantor')->columns(2)->schema([
                TextInput::make('kantor.nama')->label('Nama kantor')->required()->maxLength(150),
                TextInput::make('kantor.kota')->label('Kota')->maxLength(100),
                TextInput::make('kantor.telepon')->label('Telepon')->maxLength(50),
                Select::make('kantor.zona_waktu')->label('Zona waktu jam akta')->required()
                    ->options(['WIB' => 'WIB (Waktu Indonesia Barat)', 'WITA' => 'WITA (Waktu Indonesia Tengah)', 'WIT' => 'WIT (Waktu Indonesia Timur)']),
                TextInput::make('nomor.berkas')->label('Awalan nomor berkas')->required()->maxLength(10)->alphaDash(),
            ]),
            Section::make('Kecerdasan buatan (AI)')
                ->description($tanpaKunci
                    ? 'Kunci OPENAI_API_KEY belum diisi di berkas .env, sehingga fitur AI tidak dapat dipakai.'
                    : sprintf('Biaya bulan ini USD %s. Hanya notaris yang dapat mengubah bagian ini.', number_format(AiPermintaan::biayaBulanIni(), 2)))
                ->columns(2)->schema([
                    Toggle::make('ai.aktif')->label('Izinkan fitur AI')->disabled($aiTerkunci)->live()->columnSpanFull()
                        ->helperText('Data dikirim ke penyedia AI hanya setelah disamarkan (NIK, NPWP, nomor telepon, dan nama diganti token), hanya untuk klien yang memberi persetujuan, dan tidak untuk berkas sangat rahasia. Setiap panggilan tercatat di Log AI.'),
                    Toggle::make('ai.ekstraksi')->label('Ekstraksi identitas dari KTP/dokumen')->disabled($aiTerkunci)
                        ->visible(fn (Get $get) => (bool) $get('ai.aktif'))
                        ->helperText('Gambar dokumen identitas dikirim untuk dibaca. Hasil wajib diperiksa staf sebelum disimpan.'),
                    Toggle::make('ai.pemeriksa')->label('Pemeriksa draf akta')->disabled($aiTerkunci)
                        ->visible(fn (Get $get) => (bool) $get('ai.aktif'))
                        ->helperText('Teks draf yang sudah disamarkan dikirim untuk dicari ketidaksesuaian. Bukan pengganti pemeriksaan notaris.'),
                    TextInput::make('ai.batas_bulanan_usd')->label('Batas biaya per bulan (USD)')->numeric()->minValue(0)->required()->disabled($aiTerkunci)
                        ->visible(fn (Get $get) => (bool) $get('ai.aktif')),
                ]),
        ]);
    }

    public function simpan(): void
    {
        $data = $this->form->getState();
        foreach (self::KUNCI as $k) {
            if (str_starts_with($k, 'ai.') && ! static::isNotaris()) {
                continue;
            }
            $nilai = data_get($data, $k);
            if (str_starts_with($k, 'ai.') && $k !== 'ai.batas_bulanan_usd') {
                $nilai = (bool) $nilai;
            }
            if ($k === 'ai.batas_bulanan_usd' && $nilai === null) {
                continue;
            }
            Pengaturan::set($k, $nilai);
        }
        Notification::make()->success()->title('Pengaturan disimpan.')->send();
    }
}
