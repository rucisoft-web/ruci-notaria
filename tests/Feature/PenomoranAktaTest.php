<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatusAkta;
use App\Models\Akta;
use App\Models\Tenggat;
use App\Services\PenomoranAkta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use LogicException;
use Tests\TestCase;

class PenomoranAktaTest extends TestCase
{
    use RefreshDatabase;

    private function nomori(Akta $akta, string $tanggal, string $jam = '10:00', array $hasil = []): Akta
    {
        return app(PenomoranAkta::class)->beriNomor($akta, Carbon::parse($tanggal), $jam, $this->notaris ??= $this->pengguna(Role::Notaris), $hasil + ['alasan_kesampingkan' => null]);
    }

    private $notaris;

    public function test_nomor_bulanan_mulai_ulang_dan_nomor_urut_berlanjut(): void
    {
        $this->travelTo('2026-10-24 12:00');
        $j = $this->jabatan();
        $a = $this->nomori($this->aktaSiap($j), '2026-09-29');
        $b = $this->nomori($this->aktaSiap($j), '2026-09-30');
        $c = $this->nomori($this->aktaSiap($j), '2026-10-01');

        $this->assertSame([1, 1], [$a->nomor_bulanan, $a->nomor_urut]);
        $this->assertSame([2, 2], [$b->nomor_bulanan, $b->nomor_urut]);
        $this->assertSame([1, 3], [$c->nomor_bulanan, $c->nomor_urut]);
        $this->assertSame(StatusAkta::Ditandatangani, $c->status);

        // Notaris lain punya urutan sendiri.
        $lain = $this->nomori($this->aktaSiap($this->jabatan(['kode' => 'NL'])), '2026-10-02');
        $this->assertSame([1, 1], [$lain->nomor_bulanan, $lain->nomor_urut]);
    }

    public function test_tanggal_dan_jam_tidak_boleh_mundur(): void
    {
        $this->travelTo('2026-10-24 12:00');
        $j = $this->jabatan();
        $this->nomori($this->aktaSiap($j), '2026-10-10', '14:00');

        $this->assertGagal(fn () => $this->nomori($this->aktaSiap($j), '2026-10-09'), 'tanggal');
        $this->assertGagal(fn () => $this->nomori($this->aktaSiap($j), '2026-10-10', '13:59'), 'jam');
        $this->assertGagal(fn () => $this->nomori($this->aktaSiap($j), '2026-10-26'), 'tanggal');
        $this->assertSame(2, $this->nomori($this->aktaSiap($j), '2026-10-10', '14:00')->nomor_bulanan);
    }

    public function test_hanya_notaris_dan_temuan_kritis_menahan_penomoran(): void
    {
        $this->travelTo('2026-10-24 12:00');
        $j = $this->jabatan();
        $akta = $this->aktaSiap($j);

        $this->assertGagal(fn () => app(PenomoranAkta::class)->beriNomor($akta, Carbon::parse('2026-10-20'), '10:00', $this->pengguna(Role::Staf)), 'akta');

        $akta->saksi()->first()->delete();
        $this->assertGagal(fn () => $this->nomori($akta->fresh(), '2026-10-20'), 'akta');
        $this->assertNull($akta->fresh()->nomor_urut);

        // Notaris dapat mengesampingkan dengan alasan tertulis; alasannya tersimpan.
        $akta = $this->nomori($akta->fresh(), '2026-10-20', '10:00', ['alasan_kesampingkan' => 'Saksi kedua hadir, data menyusul']);
        $this->assertStringContainsString('dikesampingkan', $akta->catatan_penandatanganan);
    }

    public function test_akta_bernomor_terkunci_dan_tidak_dapat_dihapus(): void
    {
        $this->travelTo('2026-10-24 12:00');
        $akta = $this->nomori($this->aktaSiap($this->jabatan()), '2026-10-20');

        try {
            $akta->update(['judul' => 'Judul lain']);
            $this->fail('Judul akta bernomor tidak boleh berubah.');
        } catch (LogicException) {
        }
        $this->expectException(LogicException::class);
        $akta->delete();
    }

    public function test_potret_penghadap_dan_tenggat_dibuat(): void
    {
        $this->travelTo('2026-10-24 12:00');
        $akta = $this->nomori($this->aktaSiap($this->jabatan()), '2026-10-20');
        $p = $akta->penghadap()->first();
        $namaSaatTtd = $p->subjek->nama;
        $p->subjek->update(['nama' => 'Nama Baru Setelah Akta']);

        $this->assertSame($namaSaatTtd, $p->fresh()->data()['nama']);
        $this->assertSame('2026-10-29', Tenggat::where('akta_id', $akta->id)->where('kode', 'T-SAL')->first()->jatuh_tempo->toDateString());
    }

    private function assertGagal(callable $fn, string $kunci): void
    {
        try {
            $fn();
            $this->fail("Seharusnya gagal pada {$kunci}.");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($kunci, $e->errors());
        }
    }
}
