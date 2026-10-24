<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Akta;
use App\Models\AuditLog;
use App\Models\Jabatan;
use App\Models\SuratBawahTangan;
use App\Services\BukuDaftar;
use App\Services\KesiapanPemeriksaan;
use App\Services\PenjilidanMinuta;
use App\Services\PenomoranSurat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProtokolTest extends TestCase
{
    use RefreshDatabase;

    /** Membuat akta bernomor secara langsung (tanpa layanan penomoran) untuk menguji penjilidan dan buku daftar. */
    private function aktaBernomor(int $jabatanId, int $jumlah, int $bulan = 9): void
    {
        $berkas = $this->berkas(Jabatan::find($jabatanId));
        for ($i = 1; $i <= $jumlah; $i++) {
            $a = Akta::create(['berkas_id' => $berkas->id, 'judul' => "Akta {$i}"]);
            $a->forceFill(['tahun' => 2026, 'bulan' => $bulan, 'nomor_bulanan' => $i, 'nomor_urut' => $i, 'tanggal' => sprintf('2026-%02d-%02d', $bulan, min(28, 1 + intdiv($i, 5))), 'jam' => '10:00:00', 'status' => 'ditandatangani'])->save();
        }
    }

    public function test_jilid_paling_banyak_lima_puluh_minuta(): void
    {
        $j = $this->jabatan();
        $this->aktaBernomor($j->id, 120);

        $jilid = app(PenjilidanMinuta::class)->susun($j->id, 2026, 9);

        $this->assertSame([50, 50, 20], array_map(fn ($x) => $x->jumlah_minuta, $jilid));
        $this->assertSame([101, 120], [$jilid[2]->nomor_awal, $jilid[2]->nomor_akhir]);

        $jilid[0]->update(['dijilid_at' => now()]);
        $this->expectException(ValidationException::class);
        app(PenjilidanMinuta::class)->susun($j->id, 2026, 9);
    }

    public function test_nomor_surat_di_bawah_tangan_per_jenis_dan_tahun(): void
    {
        $j = $this->jabatan(['kode' => 'RK']);
        $catat = fn (string $jenis, string $tgl) => app(PenomoranSurat::class)->catat(['jabatan_id' => $j->id, 'jenis' => $jenis, 'tanggal' => $tgl, 'judul' => 'Surat', 'para_pihak' => 'A']);

        $this->assertSame('1/Leg/RK/2026', $catat('legalisasi', '2026-10-01')->nomor);
        $this->assertSame('2/Leg/RK/2026', $catat('legalisasi', '2026-10-02')->nomor);
        $this->assertSame('1/W/RK/2026', $catat('waarmerking', '2026-10-02')->nomor);
        $this->assertSame('1/FK/RK/2026', $catat('fotokopi', '2026-10-02')->nomor);
        $this->assertSame('1/Leg/RK/2027', $catat('legalisasi', '2027-01-02')->nomor);

        $this->expectException(\LogicException::class);
        SuratBawahTangan::first()->delete();
    }

    public function test_buku_daftar_klapper_dan_celah_nomor(): void
    {
        $j = $this->jabatan();
        $this->aktaBernomor($j->id, 3, 10);
        $akta = Akta::where('nomor_urut', 1)->first();
        $akta->penghadap()->create(['subjek_id' => $this->orang(['nama' => 'Dr. Zainal Abidin, S.H.'])->id]);
        $akta->penghadap()->create(['subjek_id' => $this->orang(['nama' => 'Ahmad Yani'])->id]);

        $buku = app(BukuDaftar::class);
        $this->assertCount(3, $buku->isi('repertorium', $j->id, 2026, 10));
        $this->assertSame(['A', 'Z'], $buku->isi('klapper', $j->id, 2026, 10)->keys()->all());
        $this->assertTrue($buku->isi('wasiat', $j->id, 2026, 10)->isEmpty());

        $this->assertSame([], app(KesiapanPemeriksaan::class)->celahNomor($j->id, 2026));
        DB::table('akta')->where('nomor_urut', 2)->update(['nomor_urut' => null, 'nomor_bulanan' => null]);
        $this->assertSame([2], app(KesiapanPemeriksaan::class)->celahNomor($j->id, 2026));
    }

    public function test_jejak_audit_mendeteksi_perubahan_langsung(): void
    {
        $this->actingAs($this->pengguna(Role::Notaris));
        $this->berkas($this->jabatan());
        $this->assertNull(AuditLog::verifyChain());
        $this->artisan('notaria:periksa-audit')->assertSuccessful();

        $id = AuditLog::query()->orderBy('id')->skip(1)->value('id');
        DB::table('audit_logs')->where('id', $id)->update(['label' => 'diubah diam-diam']);

        $this->assertSame($id, AuditLog::verifyChain());
        $this->artisan('notaria:periksa-audit')->assertFailed();
    }
}
