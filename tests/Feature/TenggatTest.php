<?php

namespace Tests\Feature;

use App\Models\HariLibur;
use App\Models\Tenggat;
use App\Services\KalkulatorTenggat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TenggatTest extends TestCase
{
    use RefreshDatabase;

    public function test_hari_kerja_melewati_akhir_pekan_dan_hari_libur(): void
    {
        HariLibur::create(['tanggal' => '2026-12-25', 'nama' => 'Natal']);
        $k = app(KalkulatorTenggat::class);

        // Jumat 18 Des + 7 hari kerja: 21,22,23,24,(25 libur),28,29,30 → Rabu 30 Des.
        $this->assertSame('2026-12-30', $k->hitung(Carbon::parse('2026-12-18'), 7, true)->toDateString());
        $this->assertSame('2026-12-17', $k->hitung(Carbon::parse('2026-11-17'), 30, false)->toDateString());
    }

    public function test_tenggat_bulanan_dibuat_sekali_per_notaris(): void
    {
        $this->jabatan();
        $this->jabatan(['kode' => 'NB']);
        $k = app(KalkulatorTenggat::class);

        $this->assertSame(6, $k->buatBulanan(Carbon::parse('2026-09-01')));
        $this->assertSame(0, $k->buatBulanan(Carbon::parse('2026-09-15')));
        $this->assertSame('2026-10-15', Tenggat::where('kode', 'T-MPD')->first()->jatuh_tempo->toDateString());
        $this->assertSame('2026-10-05', Tenggat::where('kode', 'T-WST')->first()->jatuh_tempo->toDateString());

        $this->artisan('notaria:tenggat-bulanan', ['--periode' => '2026-10'])->assertSuccessful();
        $this->assertSame(12, Tenggat::count());
    }
}
