<?php

namespace Tests\Feature;

use App\Models\Akta;
use App\Models\Berkas;
use App\Models\DrafAkta;
use App\Models\JilidMinuta;
use App\Models\Pembayaran;
use App\Models\SuratBawahTangan;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\BukuDaftar;
use App\Services\PenjilidanMinuta;
use Database\Seeders\DemoSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Semua layar terbuka tanpa galat untuk setiap peran (403/404 berarti ditolak atau tersembunyi), dan hak akses sesuai peran. */
class PanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo('2026-10-24 09:00');
        $this->seed(DemoSeeder::class);
    }

    private function user(string $kunci): User
    {
        return User::where('email', "{$kunci}@notaria.demo")->firstOrFail();
    }

    /** Berganti pengguna dengan sesi baru (AuthenticateSession menolak sesi milik pengguna lain). */
    private function sebagai(string $kunci): void
    {
        $this->flushSession();
        $this->actingAs($this->user($kunci));
    }

    /** @return list<string> */
    private function semuaUrl(): array
    {
        $panel = Filament::getPanel('app');
        $urls = [];
        foreach ($panel->getResources() as $r) {
            $urls[] = $r::getUrl('index', panel: 'app');
            $model = $r::getModel();
            $record = $model::query()->first();
            foreach (['view', 'edit'] as $page) {
                if ($record && $r::hasPage($page)) {
                    $urls[] = $r::getUrl($page, ['record' => $record], panel: 'app');
                }
            }
        }
        foreach ($panel->getPages() as $p) {
            $urls[] = $p::getUrl(panel: 'app');
        }

        return array_values(array_unique($urls));
    }

    public function test_semua_layar_tanpa_galat_untuk_setiap_peran(): void
    {
        $urls = $this->semuaUrl();
        $this->assertGreaterThan(25, count($urls));

        foreach (['notaris', 'staf', 'keuangan', 'arsip', 'admin'] as $peran) {
            $this->sebagai($peran);
            foreach ($urls as $url) {
                $status = $this->get($url)->getStatusCode();
                $this->assertContains($status, [200, 403, 404], "{$peran} {$url} → {$status}");
            }
        }
    }

    public function test_notaris_melihat_semua_layar_utama(): void
    {
        $this->sebagai('notaris');
        foreach ($this->semuaUrl() as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/app')->assertSee('Tenggat 7 hari ke depan')->assertSee('Pendirian Perseroan Terbatas PT Sinar Kopi Priangan');
    }

    public function test_hak_akses_per_peran(): void
    {
        $rahasia = Berkas::where('sangat_rahasia', true)->firstOrFail();

        $this->sebagai('staf2');
        $this->get('/app/berkas/'.$rahasia->id)->assertNotFound();
        $this->get('/app/tagihan')->assertForbidden();
        $this->get('/app/pengguna')->assertForbidden();

        $this->sebagai('notaris');
        $this->get('/app/berkas/'.$rahasia->id)->assertOk();

        $this->sebagai('admin');
        $this->get('/app/berkas')->assertForbidden();
        $this->get('/app/pengguna')->assertOk();
        $this->get(route('cetak.lembar-ttd', Akta::first()))->assertForbidden();

        $this->sebagai('staf');
        $this->get(route('cetak.tagihan', Tagihan::first()))->assertForbidden();
    }

    public function test_semua_cetakan_terbuka(): void
    {
        $akta = Akta::whereNotNull('nomor_urut')->first();
        app(PenjilidanMinuta::class)->susun($akta->jabatan_id, 2026, 9);
        $this->sebagai('notaris');
        $berkas = Berkas::has('tagihan')->first();
        $berkas->tandaTerima()->create(['arah' => 'masuk', 'tanggal' => today(), 'pihak' => 'Hendra Wijaya', 'daftar_dokumen' => "Sertipikat asli\nKTP"]);

        $params = ['jabatan' => $akta->jabatan_id, 'tahun' => 2026, 'bulan' => 10];
        $this->get(route('cetak.laporan-mpd', $params))->assertOk()->assertSee('Majelis Pengawas Daerah')->assertSee('NIHIL')->assertSee('Jaminan Fidusia');
        foreach (array_keys(BukuDaftar::JENIS) as $jenis) {
            $this->get(route('cetak.buku', $params + ['jenis' => $jenis]))->assertOk();
        }
        $this->get(route('cetak.buku', $params + ['jenis' => 'tidak-ada']))->assertNotFound();
        $this->get(route('cetak.lembar-ttd', $akta))->assertOk();
        $this->get(route('cetak.pengesahan', SuratBawahTangan::first()))->assertOk()->assertSee('1/Leg/RK/2026');
        $this->get(route('cetak.label-jilid', JilidMinuta::first()))->assertOk();
        $this->get(route('cetak.tagihan', Tagihan::first()))->assertOk()->assertSee('Honorarium akta PPJB');
        $this->get(route('cetak.kuitansi', Pembayaran::first()))->assertOk()->assertSee('lima juta Rupiah');
        $this->get(route('cetak.tanda-terima', $berkas->tandaTerima()->first()))->assertOk()->assertSee('Sertipikat asli');
        $this->get(route('draf.unduh', DrafAkta::first()))->assertOk();
    }
}
