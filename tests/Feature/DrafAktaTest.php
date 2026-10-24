<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\StatusTemplate;
use App\Models\TemplateAkta;
use App\Services\PemeriksaDraf;
use App\Services\PenomoranAkta;
use App\Services\PenyusunDraf;
use App\Services\TemplateBawaan;
use App\Support\Docx;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DrafAktaTest extends TestCase
{
    use RefreshDatabase;

    private function template(StatusTemplate $status = StatusTemplate::Disetujui): TemplateAkta
    {
        $path = app(TemplateBawaan::class)->aktaUmum('template/uji-'.uniqid().'.docx');

        return TemplateAkta::create(['nama' => 'Uji', 'versi' => 1, 'path' => $path, 'status' => $status]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_template_belum_disetujui_ditolak(): void
    {
        $this->expectException(ValidationException::class);
        app(PenyusunDraf::class)->buat($this->aktaSiap($this->jabatan()), $this->template(StatusTemplate::Draf));
    }

    public function test_draf_berisi_komparisi_saksi_dan_nomor_setelah_akta_bernomor(): void
    {
        $this->travelTo('2026-10-24 12:00');
        $akta = $this->aktaSiap($this->jabatan());
        $akta->penghadap()->first()->subjek->update(['nama' => 'Budi & Rekan <Santoso>']);
        $template = $this->template();

        $draf = app(PenyusunDraf::class)->buat($akta->fresh(), $template);
        $teks = Docx::teks(Storage::disk('local')->path($draf->path));

        $this->assertStringContainsString('BUDI & REKAN <SANTOSO>', $teks, 'Karakter khusus harus di-escape, bukan merusak XML.');
        $this->assertStringContainsString('…', $teks, 'Nomor masih titik-titik sebelum bernomor.');
        $this->assertStringNotContainsString('${', $teks);
        foreach ($akta->saksi as $s) {
            $this->assertStringContainsString(mb_strtoupper($s->subjek->nama), $teks);
        }

        // Garis isi: paragraf isi diakhiri tab berpengisi strip.
        $xml = (new \ZipArchive);
        $xml->open(Storage::disk('local')->path($draf->path));
        $this->assertStringContainsString('w:leader="hyphen"', $xml->getFromName('word/document.xml'));
        $xml->close();

        app(PenomoranAkta::class)->beriNomor($akta->fresh(), Carbon::parse('2026-10-20'), '10:30', $this->pengguna(Role::Notaris), ['alasan_kesampingkan' => 'uji']);
        $draf2 = app(PenyusunDraf::class)->buat($akta->fresh(), $template);
        $teks2 = Docx::teks(Storage::disk('local')->path($draf2->path));
        $this->assertSame(2, $draf2->versi);
        $this->assertStringContainsString('Nomor: 1', $teks2);
        $this->assertStringContainsString('hari Selasa, tanggal dua puluh Oktober dua ribu dua puluh enam (20-10-2026)', $teks2);
        $this->assertStringContainsString('sepuluh lewat tiga puluh menit', $teks2);
    }

    public function test_pemeriksa_draf_menemukan_kesalahan_umum(): void
    {
        $akta = $this->aktaSiap($this->jabatan());
        $nama = $akta->penghadap()->first()->subjek->nama;
        $teks = "Penghadap {$nama}. Harga Rp1.500.000,00 (satu juta lima ratus ribu Rupiah) dan Rp2.000.000,00 (dua juta lima ratus ribu Rupiah). "
            .'Jangka waktu 12 (dua belas) bulan, denda 3 (empat) persen. ${belum_terisi} [isi pasal]';

        $temuan = collect(app(PemeriksaDraf::class)->periksaTeks($teks, $akta));
        $pesan = $temuan->pluck('pesan')->implode("\n");

        $this->assertStringContainsString('belum_terisi', $pesan);
        $this->assertStringContainsString('kurung siku', $pesan);
        $this->assertSame(2, $temuan->where('kode', 'R-07')->count(), $pesan);
        $this->assertStringContainsString('tidak ditemukan dalam draf', $pesan, 'Penghadap kedua tidak disebut.');
    }
}
