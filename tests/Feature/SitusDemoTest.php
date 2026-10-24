<?php

namespace Tests\Feature;

use App\Http\Controllers\TamuController;
use App\Models\Akta;
use App\Models\Jabatan;
use App\Models\User;
use App\Services\Ai\AiGerbang;
use App\Services\KesiapanAkta;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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
        $this->assertSame(11, Akta::query()->bernomor()->count());
        foreach (Jabatan::all() as $j) {
            $this->assertNotNull($j->user, "Jabatan {$j->kode} punya akun notaris");
            $this->assertGreaterThan(0, Akta::query()->where('jabatan_id', $j->id)->bernomor()->count());
        }
        $this->assertTrue(User::where('email', TamuController::EMAIL)->firstOrFail()->isTamu());
    }

    public function test_tombol_tamu_hanya_muncul_di_situs_demo(): void
    {
        $this->get('/app/login')->assertOk()->assertSee('Masuk sebagai tamu');

        config(['notaria.demo.tamu' => false]);
        $this->get('/app/login')->assertOk()->assertDontSee('Masuk sebagai tamu');
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

    public function test_reset_demo_menolak_di_situs_produksi(): void
    {
        config(['notaria.demo.enabled' => false]);
        $this->artisan('notaria:demo-reset')->assertFailed();
    }
}
