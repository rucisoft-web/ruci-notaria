<?php

namespace Tests\Feature;

use App\Models\RelasiSubjek;
use App\Services\KesiapanAkta;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KesiapanAktaTest extends TestCase
{
    use RefreshDatabase;

    private function kode(array $temuan, string $tingkat = 'kritis'): array
    {
        return collect($temuan)->where('tingkat', $tingkat)->pluck('kode')->unique()->values()->all();
    }

    public function test_akta_lengkap_tanpa_temuan_kritis(): void
    {
        $akta = $this->aktaSiap($this->jabatan());

        $this->assertSame([], $this->kode(app(KesiapanAkta::class)->periksa($akta, Carbon::parse('2026-10-20'))));
    }

    public function test_hubungan_keluarga_notaris_dan_saksi_ditolak(): void
    {
        $notarisSubjek = $this->orang(['nama' => 'Notaris Uji']);
        $j = $this->jabatan(['subjek_id' => $notarisSubjek->id]);
        $akta = $this->aktaSiap($j);
        $penghadap = $akta->penghadap()->first()->subjek;
        $saksi = $akta->saksi()->first()->subjek;

        // Pasal 52: penghadap adalah keponakan notaris (garis samping derajat 3).
        RelasiSubjek::create(['subjek_id' => $notarisSubjek->id, 'terkait_id' => $penghadap->id, 'hubungan' => 'keponakan']);
        // Pasal 40: saksi adalah istri penghadap, dicatat dari sisi penghadap (relasi dua arah).
        RelasiSubjek::create(['subjek_id' => $penghadap->id, 'terkait_id' => $saksi->id, 'hubungan' => 'suami_istri']);

        $kode = $this->kode(app(KesiapanAkta::class)->periksa($akta->fresh(), Carbon::parse('2026-10-20')));
        $this->assertContains('R-05', $kode);
        $this->assertContains('R-04', $kode);
    }

    public function test_sepupu_derajat_empat_tidak_dilarang(): void
    {
        $notarisSubjek = $this->orang();
        $akta = $this->aktaSiap($this->jabatan(['subjek_id' => $notarisSubjek->id]));
        RelasiSubjek::create(['subjek_id' => $notarisSubjek->id, 'terkait_id' => $akta->penghadap()->first()->subjek_id, 'hubungan' => 'sepupu']);

        $this->assertNotContains('R-05', $this->kode(app(KesiapanAkta::class)->periksa($akta->fresh(), Carbon::parse('2026-10-20'))));
    }

    public function test_umur_wilayah_jabatan_cuti_dan_wasiat_bawah_tangan(): void
    {
        $j = $this->jabatan(['cuti_mulai' => '2026-10-01', 'cuti_selesai' => '2026-10-31']);
        $akta = $this->aktaSiap($j, ['provinsi_tanda_tangan' => 'DKI Jakarta', 'kategori' => 'wasiat_bawah_tangan']);
        $akta->penghadap()->first()->subjek->update(['tanggal_lahir' => '2010-01-01', 'status_perkawinan' => 'belum_kawin']);

        $kode = $this->kode(app(KesiapanAkta::class)->periksa($akta->fresh(), Carbon::parse('2026-10-20')));
        foreach (['R-02', 'R-13', 'R-15', 'R-03'] as $k) {
            $this->assertContains($k, $kode, "Temuan {$k} seharusnya muncul.");
        }
    }
}
