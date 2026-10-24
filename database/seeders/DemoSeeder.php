<?php

namespace Database\Seeders;

use App\Enums\Kedudukan;
use App\Enums\Role;
use App\Enums\StatusAkta;
use App\Enums\StatusBerkas;
use App\Enums\StatusTagihan;
use App\Enums\StatusTemplate;
use App\Models\Akta;
use App\Models\Berkas;
use App\Models\HariLibur;
use App\Models\Jabatan;
use App\Models\JenisLayanan;
use App\Models\LokasiArsip;
use App\Models\Pengaturan;
use App\Models\Subjek;
use App\Models\Tagihan;
use App\Models\TemplateAkta;
use App\Models\User;
use App\Services\KalkulatorTenggat;
use App\Services\PenomoranAkta;
use App\Services\PenomoranSurat;
use App\Services\PenyusunDraf;
use App\Services\TemplateBawaan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh kantor notaris fiktif di Bandung. Semua nama, NIK, dan alamat rekaan.
 * Akta diberi nomor melalui layanan penomoran yang sama dengan aplikasi sehingga aturan UUJN ikut teruji.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (Jabatan::query()->where('kode', 'RK')->exists()) {
            $this->command?->warn('Data contoh sudah ada.');

            return;
        }

        $this->call(JenisLayananSeeder::class);
        $sandi = config('notaria.demo.password');

        DB::transaction(function () use ($sandi) {
            Pengaturan::set('kantor.nama', 'Kantor Notaris Dr. Rina Kartika, S.H., M.Kn.');
            Pengaturan::set('kantor.kota', 'Bandung');
            Pengaturan::set('kantor.telepon', '022-0000-1234');

            $u = [];
            foreach ([
                ['notaris', 'Dr. Rina Kartika', Role::Notaris],
                ['staf', 'Dimas Prasetyo', Role::Staf],
                ['staf2', 'Sari Wulandari', Role::Staf],
                ['keuangan', 'Lina Marlina', Role::Keuangan],
                ['arsip', 'Agus Setiawan', Role::Arsiparis],
                ['admin', 'Admin TI', Role::Admin],
            ] as [$kunci, $nama, $role]) {
                $u[$kunci] = User::updateOrCreate(['email' => "{$kunci}@notaria.demo"], ['name' => $nama, 'password' => $sandi, 'role' => $role, 'is_active' => true]);
            }
            Auth::setUser($u['notaris']); // tanpa sesi: hanya untuk created_by dan jejak audit

            $jabatan = Jabatan::create([
                'nama' => 'Rina Kartika', 'nama_lengkap' => 'Doktor RINA KARTIKA, Sarjana Hukum, Magister Kenotariatan', 'jenis' => 'notaris',
                'nomor_sk' => 'AHU-00123.AH.02.01.Tahun 2015', 'tanggal_sk' => '2015-03-02', 'tanggal_sumpah' => '2015-04-10', 'tanggal_lahir' => '1978-06-14',
                'tempat_kedudukan' => 'Kota Bandung', 'provinsi' => 'Jawa Barat', 'alamat_kantor' => 'Jalan Contoh Raya Nomor 10, Bandung 40115',
                'kode' => 'RK', 'is_ppat' => true, 'daerah_kerja_ppat' => 'Kota Bandung', 'user_id' => $u['notaris']->id, 'is_active' => true,
            ]);

            foreach ([['2026-12-25', 'Hari Raya Natal'], ['2026-12-24', 'Cuti bersama Natal'], ['2027-01-01', 'Tahun Baru Masehi']] as [$t, $n]) {
                HariLibur::firstOrCreate(['tanggal' => $t], ['nama' => $n]);
            }
            LokasiArsip::create(['kode' => 'L-A1', 'nama' => 'Lemari tahan api A, rak 1']);

            $orang = fn (array $a) => Subjek::create($a + ['jenis' => 'orang', 'jenis_identitas' => 'ktp', 'kewarganegaraan' => 'Indonesia', 'kota' => 'Kota Bandung', 'provinsi' => 'Jawa Barat']);
            $saksi1 = $orang(['nama' => 'Dimas Prasetyo', 'nik' => '3273011203950001', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '1995-03-12', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Karyawan swasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Melati Nomor 3', 'rt_rw' => '002/005', 'kelurahan' => 'Cihapit', 'kecamatan' => 'Bandung Wetan', 'saksi_kantor' => true]);
            $saksi2 = $orang(['nama' => 'Sari Wulandari', 'nik' => '3273015507970002', 'tempat_lahir' => 'Garut', 'tanggal_lahir' => '1997-07-15', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Karyawan swasta', 'status_perkawinan' => 'belum_kawin', 'alamat' => 'Jalan Kenanga Nomor 8', 'rt_rw' => '001/003', 'kelurahan' => 'Lebakgede', 'kecamatan' => 'Coblong', 'saksi_kantor' => true]);
            $budi = $orang(['nama' => 'Budi Santoso', 'nik' => '3273022001800003', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '1980-01-20', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Wiraswasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Merdeka Nomor 21', 'rt_rw' => '004/002', 'kelurahan' => 'Babakan Ciamis', 'kecamatan' => 'Sumur Bandung', 'telepon' => '0812000001', 'persetujuan_ai' => true]);
            $ani = $orang(['nama' => 'Ani Lestari', 'nik' => '3273026503850004', 'tempat_lahir' => 'Cimahi', 'tanggal_lahir' => '1985-03-25', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Wiraswasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Merdeka Nomor 21', 'rt_rw' => '004/002', 'kelurahan' => 'Babakan Ciamis', 'kecamatan' => 'Sumur Bandung']);
            $hendra = $orang(['nama' => 'Hendra Wijaya', 'nik' => '3273031108750005', 'tempat_lahir' => 'Jakarta', 'tanggal_lahir' => '1975-08-11', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Karyawan BUMN', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Dago Nomor 100', 'rt_rw' => '003/007', 'kelurahan' => 'Dago', 'kecamatan' => 'Coblong']);
            $maya = $orang(['nama' => 'Maya Puspita', 'nik' => '3273034409900006', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '1990-09-04', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Pegawai swasta', 'status_perkawinan' => 'belum_kawin', 'alamat' => 'Jalan Riau Nomor 5', 'rt_rw' => '002/001', 'kelurahan' => 'Citarum', 'kecamatan' => 'Bandung Wetan']);
            $bank = Subjek::create(['jenis' => 'badan', 'nama' => 'PT Bank Contoh Sejahtera Tbk', 'bentuk_badan' => 'pt', 'nomor_sk_badan' => 'AHU-0001.AH.01.01.Tahun 2010', 'nib' => '9120000000001', 'alamat' => 'Jalan Asia Afrika Nomor 1', 'kota' => 'Kota Bandung', 'provinsi' => 'Jawa Barat']);
            $budi->relasi()->create(['terkait_id' => $ani->id, 'hubungan' => 'suami_istri']);

            $layanan = JenisLayanan::query()->pluck('id', 'kode');
            $berkas = fn (string $kode, string $judul, Subjek $klien, string $status, array $lain = []) => Berkas::create($lain + [
                'judul' => $judul, 'jenis_layanan_id' => $layanan[$kode], 'jabatan_id' => $jabatan->id, 'klien_id' => $klien->id,
                'pic_id' => $u['staf']->id, 'status' => $status,
            ]);

            $tplPath = app(TemplateBawaan::class)->aktaUmum('template/akta-umum-v1.docx');
            $template = TemplateAkta::create(['nama' => 'Akta Umum (contoh)', 'versi' => 1, 'path' => $tplPath, 'status' => StatusTemplate::Disetujui,
                'disetujui_oleh' => $u['notaris']->id, 'disetujui_at' => now(), 'catatan' => 'Template contoh bawaan aplikasi.']);

            $akta = function (Berkas $b, string $judul, array $penghadap, ?float $nilai = null) use ($saksi1, $saksi2) {
                $a = Akta::create(['berkas_id' => $b->id, 'judul' => $judul, 'jenis_layanan_id' => $b->jenis_layanan_id, 'status' => StatusAkta::Draf,
                    'tempat' => 'Kantor Notaris', 'kota_tanda_tangan' => 'Kota Bandung', 'provinsi_tanda_tangan' => 'Jawa Barat', 'nilai_ekonomis' => $nilai]);
                foreach ($penghadap as $i => [$s, $kedudukan, $mewakili, $ket]) {
                    $a->penghadap()->create(['subjek_id' => $s->id, 'kedudukan' => $kedudukan, 'mewakili_id' => $mewakili?->id, 'keterangan_kedudukan' => $ket, 'urutan' => $i + 1]);
                }
                $a->saksi()->create(['subjek_id' => $saksi1->id, 'jenis' => 'akta']);
                $a->saksi()->create(['subjek_id' => $saksi2->id, 'jenis' => 'akta']);

                return $a;
            };
            $nomori = function (Akta $a, string $tanggal, string $jam) use ($template, $u) {
                app(PenyusunDraf::class)->buat($a, $template)->update(['final' => true]);
                app(PenomoranAkta::class)->beriNomor($a, Carbon::parse($tanggal), $jam, $u['notaris'], ['dibacakan' => true]);
            };

            // September: dua akta, sudah lewat; tenggat bulanan dan penjilidan dapat dicoba.
            $b1 = $berkas('PERJANJIAN', 'Sewa ruko Jalan Merdeka', $budi, StatusBerkas::Selesai->value, ['nilai_transaksi' => 240_000_000]);
            $nomori($akta($b1, 'Perjanjian Sewa Menyewa', [[$budi, Kedudukan::DiriSendiri, null, null], [$hendra, Kedudukan::DiriSendiri, null, null]], 240_000_000), '2026-09-08', '10:00');
            $b2 = $berkas('KUASA', 'Kuasa menjual tanah Cimahi', $maya, StatusBerkas::Selesai->value);
            $nomori($akta($b2, 'Kuasa', [[$maya, Kedudukan::DiriSendiri, null, null]]), '2026-09-21', '13:30');

            // Oktober: fidusia untuk bank, dan perjanjian pengikatan jual beli.
            $b3 = $berkas('FIDUSIA', 'Fidusia kendaraan CV Maju', $bank, StatusBerkas::PascaAkta->value, ['nilai_transaksi' => 350_000_000]);
            $nomori($akta($b3, 'Jaminan Fidusia', [[$hendra, Kedudukan::MewakiliBadan, $bank, 'selaku kuasa Direksi berdasarkan Surat Kuasa Nomor 12/SK/2026'], [$budi, Kedudukan::DiriSendiri, null, null]], 350_000_000), '2026-10-06', '09:00');
            $b4 = $berkas('PERJANJIAN', 'PPJB rumah Dago', $hendra, StatusBerkas::Ditandatangani->value, ['nilai_transaksi' => 1_200_000_000]);
            $nomori($akta($b4, 'Perjanjian Pengikatan Jual Beli', [[$hendra, Kedudukan::DiriSendiri, null, null], [$maya, Kedudukan::DiriSendiri, null, null]], 1_200_000_000), '2026-10-15', '11:00');

            // Berkas aktif: pendirian PT yang dijadwalkan besok, dan wasiat yang masih mengumpulkan dokumen.
            $b5 = $berkas('PT-DIRI', 'Pendirian PT Sinar Kopi Priangan', $budi, StatusBerkas::Dijadwalkan->value);
            $a5 = $akta($b5, 'Pendirian Perseroan Terbatas PT Sinar Kopi Priangan', [[$budi, Kedudukan::DiriSendiri, null, null], [$ani, Kedudukan::DiriSendiri, null, null]], 100_000_000);
            $a5->update(['status' => StatusAkta::Dijadwalkan, 'jadwal_at' => now()->addDay()->setTime(10, 0)]);
            app(PenyusunDraf::class)->buat($a5, $template);
            $berkas('WASIAT', 'Wasiat Ibu Maya', $maya, StatusBerkas::Dokumen->value, ['sangat_rahasia' => true]);

            // Legalisasi dan waarmerking bulan ini.
            $surat = app(PenomoranSurat::class);
            $surat->catat(['jabatan_id' => $jabatan->id, 'jenis' => 'legalisasi', 'tanggal' => '2026-10-07', 'judul' => 'Surat Pernyataan Ahli Waris', 'para_pihak' => "Budi Santoso\nAni Lestari", 'jumlah_lembar' => 2]);
            $surat->catat(['jabatan_id' => $jabatan->id, 'jenis' => 'waarmerking', 'tanggal' => '2026-10-12', 'tanggal_surat' => '2026-10-01', 'judul' => 'Perjanjian Kerja Sama Distribusi', 'para_pihak' => "Hendra Wijaya\nMaya Puspita", 'jumlah_lembar' => 5]);

            // Keuangan: tagihan PPJB terbit dan dibayar sebagian.
            $t = Tagihan::create(['berkas_id' => $b4->id, 'tanggal' => '2026-10-15', 'jatuh_tempo' => '2026-10-29', 'status' => StatusTagihan::Draf]);
            $t->items()->createMany([
                ['jenis' => 'honorarium', 'uraian' => 'Honorarium akta PPJB', 'jumlah' => 7_500_000],
                ['jenis' => 'titipan', 'uraian' => 'Pengecekan sertipikat di Kantor Pertanahan', 'jumlah' => 150_000],
            ]);
            $t->update(['status' => StatusTagihan::Terbit]);
            $t->pembayaran()->create(['tanggal' => '2026-10-16', 'jumlah' => 5_000_000, 'metode' => 'transfer']);
            $b4->danaTitipan()->create(['tanggal' => '2026-10-16', 'arah' => 'masuk', 'jumlah' => 150_000, 'uraian' => 'Titipan biaya pengecekan sertipikat']);

            app(KalkulatorTenggat::class)->buatBulanan(Carbon::parse('2026-09-01'));
            Auth::forgetUser();
        });

        $this->command?->info('Data contoh siap. Masuk di /app dengan notaris@notaria.demo, staf@notaria.demo, keuangan@notaria.demo, arsip@notaria.demo, atau admin@notaria.demo (sandi: NOTARIA_DEMO_PASSWORD).');
    }
}
