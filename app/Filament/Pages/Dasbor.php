<?php

namespace App\Filament\Pages;

use App\Enums\StatusAkta;
use App\Enums\StatusBerkas;
use App\Enums\StatusTagihan;
use App\Filament\Concerns\WithRole;
use App\Filament\Resources\Tenggat\TenggatResource;
use App\Models\Akta;
use App\Models\Berkas;
use App\Models\Pengaturan;
use App\Models\SuratBawahTangan;
use App\Models\Tagihan;
use BackedEnum;
use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

/** Dasbor harian: tenggat, jadwal tanda tangan, dan posisi berkas. */
class Dasbor extends Dashboard
{
    use WithRole;

    protected static ?string $title = 'Dasbor';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected string $view = 'filament.pages.dasbor';

    /** Berkas aktif tanpa perubahan selama sekian hari dianggap macet. */
    public const HARI_MACET = 14;

    protected function getViewData(): array
    {
        $user = static::user();
        if (! static::canViewFiles()) {
            return ['bolehLihat' => false, 'kantor' => Pengaturan::get('kantor.nama')];
        }

        $tenggat = TenggatResource::getEloquentQuery()->terbuka()->whereDate('jatuh_tempo', '<=', today()->addDays(7))
            ->with('berkas', 'akta', 'jabatan')->orderBy('jatuh_tempo')->limit(30)->get();

        $jadwal = Akta::query()->terlihatOleh($user)->where('status', StatusAkta::Dijadwalkan)
            ->whereBetween('jadwal_at', [today(), today()->addDays(2)->subSecond()])
            ->with('berkas.klien', 'jabatan')->orderBy('jadwal_at')->get();

        $aktif = Berkas::query()->terlihatOleh($user)->whereIn('status', StatusBerkas::aktif());
        $perTahap = (clone $aktif)->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');
        $macet = (clone $aktif)->where('updated_at', '<', now()->subDays(self::HARI_MACET))->with('klien', 'pic')->orderBy('updated_at')->limit(10)->get();

        $bulanIni = [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];

        return [
            'bolehLihat' => true,
            'kantor' => Pengaturan::get('kantor.nama'),
            'tenggat' => $tenggat,
            'jadwal' => $jadwal,
            'perTahap' => collect(StatusBerkas::aktif())->mapWithKeys(fn (StatusBerkas $s) => [$s->getLabel() => (int) ($perTahap[$s->value] ?? 0)]),
            'macet' => $macet,
            'kpi' => [
                'berkas_aktif' => (clone $aktif)->count(),
                'akta_bulan_ini' => Akta::query()->bernomor()->whereBetween('tanggal', $bulanIni)->count(),
                'surat_bulan_ini' => SuratBawahTangan::query()->whereBetween('tanggal', $bulanIni)->count(),
                'terlambat' => $tenggat->filter(fn ($t) => $t->jatuh_tempo->lt(today()))->count(),
                'piutang' => static::canFinance()
                    ? (float) Tagihan::query()->where('status', StatusTagihan::Terbit)->withSum('items', 'jumlah')->withSum('pembayaran', 'jumlah')->get()
                        ->sum(fn ($t) => (float) $t->items_sum_jumlah - (float) $t->pembayaran_sum_jumlah)
                    : null,
            ],
        ];
    }
}
