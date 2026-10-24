<?php

namespace Tests\Feature;

use App\Filament\Pages\PengaturanKantor;
use App\Filament\Resources\Akta\Pages\ViewAkta;
use App\Filament\Resources\Tagihan\Pages\CreateTagihan;
use App\Filament\Resources\Tagihan\Pages\EditTagihan;
use App\Models\Akta;
use App\Models\Berkas;
use App\Models\Pengaturan;
use App\Models\Tagihan;
use App\Models\TemplateAkta;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/** Alur utama lewat layar Filament: aksi, formulir, dan validasinya. */
class AlurLayarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->travelTo('2026-10-24 11:00');
        $this->seed(DemoSeeder::class);
        Filament::setCurrentPanel('app');
    }

    private function sebagai(string $kunci): User
    {
        $u = User::where('email', "{$kunci}@notaria.demo")->firstOrFail();
        $this->actingAs($u);

        return $u;
    }

    public function test_notaris_memberi_nomor_dari_layar_akta(): void
    {
        $this->sebagai('notaris');
        $akta = Akta::whereNull('nomor_urut')->firstOrFail();

        Livewire::test(ViewAkta::class, ['record' => $akta->getRouteKey()])
            ->callAction('buat_draf', ['template_id' => TemplateAkta::first()->id])
            ->assertHasNoActionErrors()
            ->callAction('beri_nomor', ['tanggal' => '2026-10-24', 'jam' => '10:15', 'dibacakan' => true, 'alasan_kesampingkan' => 'Uji'])
            ->assertHasNoActionErrors();

        $akta->refresh();
        $this->assertSame([3, 5], [$akta->nomor_bulanan, $akta->nomor_urut]);
        $this->assertSame(2, $akta->draf()->count());
    }

    public function test_staf_tidak_melihat_tombol_beri_nomor(): void
    {
        $this->sebagai('staf');
        Livewire::test(ViewAkta::class, ['record' => Akta::whereNull('nomor_urut')->first()->getRouteKey()])
            ->assertActionHidden('beri_nomor')
            ->assertActionVisible('buat_draf');
    }

    public function test_tagihan_menolak_honorarium_di_atas_batas(): void
    {
        $this->sebagai('keuangan');
        $undo = Repeater::fake();
        $berkas = Berkas::where('judul', 'Pendirian PT Sinar Kopi Priangan')->firstOrFail(); // akta nilai 100 juta → batas 2,5 juta

        Livewire::test(CreateTagihan::class)
            ->fillForm(['berkas_id' => $berkas->id, 'tanggal' => '2026-10-24', 'items' => [['jenis' => 'honorarium', 'uraian' => 'Honor', 'jumlah' => 3_000_000]]])
            ->call('create')
            ->assertHasErrors(['data.items']);
        $this->assertSame(0, Tagihan::where('berkas_id', $berkas->id)->count());

        Livewire::test(CreateTagihan::class)
            ->fillForm(['berkas_id' => $berkas->id, 'tanggal' => '2026-10-24', 'items' => [
                ['jenis' => 'honorarium', 'uraian' => 'Honor', 'jumlah' => 2_500_000],
                ['jenis' => 'titipan', 'uraian' => 'PNBP AHU', 'jumlah' => 1_000_000],
            ]])
            ->call('create')
            ->assertHasNoErrors();
        $t = Tagihan::where('berkas_id', $berkas->id)->firstOrFail();
        $this->assertSame(3_500_000.0, $t->total());

        Livewire::test(EditTagihan::class, ['record' => $t->getRouteKey()])->callAction('terbit');
        $this->assertSame('terbit', $t->fresh()->status->value);
        $undo();
    }

    public function test_hanya_notaris_menyalakan_ai(): void
    {
        $this->sebagai('admin');
        Livewire::test(PengaturanKantor::class)
            ->fillForm(['kantor' => ['nama' => 'Kantor Baru'], 'ai' => ['aktif' => true]])
            ->call('simpan');
        $this->assertSame('Kantor Baru', Pengaturan::get('kantor.nama'));
        $this->assertFalse((bool) Pengaturan::get('ai.aktif'));

        $this->sebagai('notaris');
        Livewire::test(PengaturanKantor::class)
            ->fillForm(['ai' => ['aktif' => true, 'pemeriksa' => true, 'batas_bulanan_usd' => 15]])
            ->call('simpan');
        $this->assertTrue((bool) Pengaturan::get('ai.aktif'));
        $this->assertTrue((bool) Pengaturan::get('ai.pemeriksa'));
        $this->assertEquals(15, Pengaturan::get('ai.batas_bulanan_usd'));
    }
}
