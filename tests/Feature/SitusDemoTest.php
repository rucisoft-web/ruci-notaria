<?php

namespace Tests\Feature;

use App\Filament\Resources\Akta\Pages\ViewAkta;
use App\Filament\Resources\Tenggat\Pages\ManageTenggat as ListTenggat;
use App\Http\Controllers\TamuController;
use App\Models\Akta;
use App\Models\Berkas;
use App\Models\Jabatan;
use App\Models\Tenggat;
use App\Models\User;
use App\Services\Ai\AiGerbang;
use App\Services\KesiapanAkta;
use Database\Seeders\DemoSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Situs demo: data contoh tiga notaris, tombol masuk sebagai tamu beserta batasannya, dan halaman depan. */
class SitusDemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['notaria.demo.enabled' => true, 'notaria.demo.tamu' => true]);
    }

    public static function tanggal(): array
    {
        return ['akhir Oktober' => ['2026-10-24 09:00'], 'awal tahun' => ['2027-01-02 08:00'], 'awal Maret' => ['2027-03-01 07:00']];
    }

    /** Tanggal data contoh dihitung mundur dari hari ini, jadi seeder harus lolos aturan penomoran kapan pun dijalankan. */
    #[DataProvider('tanggal')]
    public function test_data_contoh_tiga_notaris_dapat_dimuat_kapan_saja(string $hari): void
    {
        $this->travelTo($hari);
        $this->seed(DemoSeeder::class);

        $this->assertEqualsCanonicalizing(['RK', 'BH', 'AP'], Jabatan::pluck('kode')->all());
        $this->assertSame(15, Akta::query()->bernomor()->count());
        foreach (Jabatan::all() as $j) {
            $this->assertNotNull($j->user, "Jabatan {$j->kode} punya akun notaris");
            $this->assertGreaterThan(0, Akta::query()->where('jabatan_id', $j->id)->bernomor()->count());
        }
        $this->assertTrue(User::where('email', TamuController::EMAIL)->firstOrFail()->isTamu());
    }

    public function test_tombol_tamu_hanya_muncul_di_situs_demo(): void
    {
        $this->get('/app/login')->assertOk()->assertSee('Masuk ke Ruci Notaria')->assertSee('Masuk sebagai Tamu');

        config(['notaria.demo.tamu' => false]);
        $this->get('/app/login')->assertOk()->assertDontSee('Masuk sebagai Tamu');
        $this->post(route('tamu.masuk'))->assertNotFound();
    }

    public function test_tamu_masuk_dan_dibatasi(): void
    {
        $this->travelTo('2026-10-24 09:00');
        $this->seed(DemoSeeder::class);

        $this->post(route('tamu.masuk'))->assertRedirect('/app');
        $this->assertAuthenticatedAs($tamu = User::where('email', TamuController::EMAIL)->first());

        $this->get('/app')->assertOk()->assertSee('DATA CONTOH')->assertSee('Grup Notaris Priangan (Data Contoh)');
        $this->get('/app/akta')->assertOk()->assertSee('Perjanjian Kerja Sama Usaha');
        $this->get('/app/profile')->assertForbidden();
        $this->get('/app/pengaturan-kantor')->assertForbidden();
        $this->assertSame('Fitur AI tidak tersedia untuk akun tamu.', app(AiGerbang::class)->alasanTolak('ekstraksi'));

        // Akun tamu tidak dapat dipakai bila situs bukan lagi situs demo.
        config(['notaria.demo.enabled' => false]);
        $this->assertFalse($tamu->canAccessPanel(filament()->getPanel('app')));
    }

    /** Tamu memilih notaris contoh di form masuk dan dapat menggantinya dari pita di atas panel. */
    public function test_tamu_memilih_notaris_contoh(): void
    {
        $this->travelTo('2026-10-24 09:00');
        $this->seed(DemoSeeder::class);
        $this->get('/app/login')->assertOk()->assertSee('Pilih notaris contoh')->assertSee('Bambang Hartono')->assertSee('Kab. Bandung Barat');

        $this->post(route('tamu.masuk'), ['notaris' => 'BH'])->assertRedirect('/app');
        $this->assertSame('BH', Jabatan::utama()->kode);
        $this->get('/app/buku-daftar')->assertOk()->assertSee('Tamu · hanya lihat')->assertSee(route('tamu.notaris', 'AP'));

        $this->from('/app/buku-daftar')->get(route('tamu.notaris', 'AP'))->assertRedirect('/app/buku-daftar');
        $this->assertSame('AP', Jabatan::utama()->kode);

        // Pilihan notaris hanya berlaku untuk tamu.
        $this->flushSession();
        $this->actingAs(User::where('email', 'notaris2@notaria.demo')->first());
        $this->get(route('tamu.notaris', 'AP'))->assertNotFound();
        $this->assertSame('RK', Jabatan::utama()->kode);
    }

    /** Tamu melihat semua layar data (tanpa galat), tetapi halaman buat/ubah, tombol pengubah, dan penyimpanan ditolak. */
    public function test_tamu_hanya_dapat_melihat(): void
    {
        $this->travelTo('2026-10-24 09:00');
        $this->seed(DemoSeeder::class);
        $this->post(route('tamu.masuk'));
        $panel = filament()->getPanel('app');

        foreach ($panel->getResources() as $r) {
            $record = $r::getModel()::query()->first();
            if ($r::canViewAny()) {
                $this->get($r::getUrl('index', panel: 'app'))->assertOk();
            }
            $this->assertFalse($r::canCreate(), "{$r} canCreate");
            if ($record) {
                $this->assertFalse($r::canEdit($record), "{$r} canEdit");
                $this->assertFalse($r::canDelete($record), "{$r} canDelete");
                if ($r::hasPage('view')) {
                    $this->get($r::getUrl('view', ['record' => $record], panel: 'app'))->assertOk();
                }
                if ($r::hasPage('edit')) {
                    $this->get($r::getUrl('edit', ['record' => $record], panel: 'app'))->assertForbidden();
                }
            }
        }
        foreach (['berkas', 'akta', 'subjek', 'tagihan', 'legalisasi-waarmerking', 'tenggat', 'buku-daftar', 'kesiapan-pemeriksaan', 'jilid-minuta', 'serah-terima-protokol', 'permintaan-penegak-hukum', 'template-akta', 'jabatan', 'jejak-audit'] as $menu) {
            $this->get('/app/'.$menu)->assertOk();
        }

        $akta = Akta::where('judul', 'Pendirian Perseroan Terbatas PT Sinar Kopi Priangan')->firstOrFail();
        Livewire::test(ViewAkta::class, ['record' => $akta->getRouteKey()])
            ->assertActionHidden('beri_nomor')->assertActionHidden('buat_draf')->assertActionHidden('jadwalkan')->assertActionHidden('edit');
        Livewire::test(ListTenggat::class)->assertTableActionHidden('selesai', Tenggat::whereNull('selesai_at')->first());

        $this->expectException(AuthorizationException::class);
        Berkas::first()->update(['judul' => 'Diubah tamu']);
    }

    public function test_saksi_bersaudara_dengan_penghadap_menjadi_temuan_kritis(): void
    {
        $this->travelTo('2026-10-24 09:00');
        $this->seed(DemoSeeder::class);

        $akta = Akta::where('judul', 'Perjanjian Kerja Sama Usaha')->firstOrFail();
        $temuan = app(KesiapanAkta::class)->periksa($akta);
        $this->assertContains('R-04', collect($temuan)->where('tingkat', 'kritis')->pluck('kode')->all());
    }

    public function test_cetakan_diberi_tanda_air_data_contoh(): void
    {
        $this->travelTo('2026-10-24 09:00');
        $this->seed(DemoSeeder::class);
        $this->actingAs(User::where('email', 'notaris2@notaria.demo')->first());

        $j = Jabatan::where('kode', 'BH')->first();
        $this->get(route('cetak.laporan-mpd', [$j, 2026, 9]))->assertOk()->assertSee('DATA CONTOH')->assertSee('BAMBANG HARTONO');
    }

    public function test_halaman_depan_untuk_linkedin(): void
    {
        config(['notaria.landing' => false]);
        $this->get('/')->assertRedirect('/app');

        config(['notaria.landing' => true]);
        $this->get('/')->assertOk()
            ->assertSee('<meta property="og:image" content="'.asset('img/og-ruci-notaria.png').'">', false)
            ->assertSee('Coba sebagai tamu')
            ->assertSee('data contoh tiga notaris')
            ->assertSee(route('tamu.masuk'));

        config(['notaria.demo.enabled' => false]);
        $this->get('/')->assertOk()->assertDontSee('Coba sebagai tamu')->assertDontSee(route('tamu.masuk'));
    }

    /** Halaman Mengapa Ruci Notaria dan panduan penggunaan terbuka tanpa login, ditautkan dari form masuk, dan ada di menu Panduan. */
    public function test_halaman_panduan(): void
    {
        $this->get(route('panduan.mengapa'))->assertOk()->assertSee('Beban yang berkurang')->assertSee('Masuk sebagai tamu');
        $this->get(route('panduan.penggunaan'))->assertOk()->assertSee('Memberi nomor akta')->assertSee('img/panduan/kesiapan.jpg', false);
        $this->get('/app/login')->assertOk()->assertSee(route('panduan.mengapa'))->assertSee(route('panduan.penggunaan'));

        config(['notaria.demo.tamu' => false]);
        $this->get('/app/login')->assertOk()->assertSee(route('panduan.mengapa'))->assertDontSee('Masuk sebagai tamu');

        config(['notaria.demo.tamu' => true]);
        $this->travelTo('2026-10-24 09:00');
        $this->seed(DemoSeeder::class);
        foreach (['notaris@notaria.demo', TamuController::EMAIL] as $email) {
            $this->flushSession();
            $this->actingAs(User::where('email', $email)->firstOrFail());
            $this->get('/app/panduan/mengapa-ruci-notaria')->assertOk()->assertSee('Rahasia jabatan');
            $this->get('/app/panduan/penggunaan')->assertOk()->assertSee('Daftar isi');
        }
        foreach (['dasbor', 'kesiapan', 'laporan-mpd', 'login', 'akta', 'salinan'] as $gambar) {
            $this->assertFileExists(public_path("img/panduan/{$gambar}.jpg"));
        }
    }

    public function test_reset_demo_menolak_di_situs_produksi(): void
    {
        config(['notaria.demo.enabled' => false]);
        $this->artisan('notaria:demo-reset')->assertFailed();
    }
}
