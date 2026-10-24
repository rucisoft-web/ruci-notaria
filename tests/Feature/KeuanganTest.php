<?php

namespace Tests\Feature;

use App\Enums\StatusAkta;
use App\Enums\StatusTagihan;
use App\Models\Tagihan;
use App\Services\Honorarium;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeuanganTest extends TestCase
{
    use RefreshDatabase;

    public function test_batas_honorarium_pasal_36(): void
    {
        $this->assertSame(2_500_000.0, Honorarium::batas(100_000_000));
        $this->assertSame(7_500_000.0, Honorarium::batas(500_000_000));
        $this->assertSame(20_000_000.0, Honorarium::batas(2_000_000_000));
        $this->assertSame(5_000_000.0, Honorarium::batas(null));
        $this->assertSame(10_000_000.0, Honorarium::batasPpat(1_000_000_000));
    }

    public function test_honorarium_berkas_dijumlahkan_per_akta_dan_dibatasi(): void
    {
        $j = $this->jabatan();
        $berkas = $this->berkas($j);
        $this->aktaSiap($j, ['nilai_ekonomis' => 100_000_000], $berkas);
        $this->aktaSiap($j, ['nilai_ekonomis' => 500_000_000], $berkas);
        $this->aktaSiap($j, ['nilai_ekonomis' => 9_000_000_000, 'status' => StatusAkta::Batal], $berkas);

        $this->assertSame(10_000_000.0, Honorarium::batasBerkas($berkas));
        $this->assertNull(Honorarium::periksa($berkas, 10_000_000));
        $this->assertNotNull(Honorarium::periksa($berkas, 10_000_001));

        $t = Tagihan::create(['berkas_id' => $berkas->id]);
        $t->items()->create(['jenis' => 'honorarium', 'uraian' => 'Honor', 'jumlah' => 6_000_000]);
        $this->assertNotNull(Honorarium::periksa($berkas, 5_000_000), 'Tagihan lain pada berkas yang sama ikut dihitung.');
        $this->assertNull(Honorarium::periksa($berkas, 5_000_000, $t->id));

        $berkas->update(['cuma_cuma' => true]);
        $this->assertStringContainsString('cuma-cuma', Honorarium::periksa($berkas->fresh(), 1));
    }

    public function test_pembayaran_melunasi_tagihan_dan_tidak_dapat_dihapus(): void
    {
        $t = Tagihan::create(['berkas_id' => $this->berkas($this->jabatan())->id]);
        $t->items()->create(['jenis' => 'honorarium', 'uraian' => 'Honor', 'jumlah' => 1_000_000]);
        $t->update(['status' => StatusTagihan::Terbit]);

        $p1 = $t->pembayaran()->create(['tanggal' => today(), 'jumlah' => 400_000]);
        $this->assertSame(StatusTagihan::Terbit, $t->fresh()->status);
        $t->pembayaran()->create(['tanggal' => today(), 'jumlah' => 600_000]);
        $this->assertSame(StatusTagihan::Lunas, $t->fresh()->status);
        $this->assertStringStartsWith('KWT', $p1->nomor_kuitansi);
        $this->assertStringStartsWith('INV', $t->nomor);

        $this->expectException(\LogicException::class);
        $p1->delete();
    }
}
