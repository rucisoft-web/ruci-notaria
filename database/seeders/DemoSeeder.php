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
use App\Models\Tenggat;
use App\Models\User;
use App\Services\KalkulatorTenggat;
use App\Services\PenomoranAkta;
use App\Services\PenomoranSurat;
use App\Services\PenyusunDraf;
use App\Services\TemplateBawaan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * DATA CONTOH: tiga kantor notaris fiktif di Jawa Barat (Kota Bandung, Kota Cimahi, Kabupaten Bandung Barat).
 * Semua nama, NIK, alamat, dan nomor rekaan. Tanggal dihitung mundur dari hari ini sehingga data selalu terasa baru.
 * Akta diberi nomor melalui layanan penomoran yang sama dengan aplikasi sehingga aturan UUJN ikut teruji.
 */
class DemoSeeder extends Seeder
{
    public const LABEL = 'Data contoh fiktif. Bukan data klien sebenarnya.';

    /** @var array<string, User> */
    private array $u = [];

    private TemplateAkta $template;

    /** @var Collection<string, int> */
    private $layanan;

    public function run(): void
    {
        if (Jabatan::query()->where('kode', 'RK')->exists()) {
            $this->command?->warn('Data contoh sudah ada.');

            return;
        }

        $this->call(JenisLayananSeeder::class);
        $sandi = config('notaria.demo.password');

        DB::transaction(function () use ($sandi) {
            Pengaturan::set('kantor.nama', 'Grup Notaris Priangan (Data Contoh)');
            Pengaturan::set('kantor.kota', 'Bandung');
            Pengaturan::set('kantor.telepon', '022-0000-1234');

            foreach ([
                ['notaris', 'Dr. Rina Kartika', Role::Notaris],
                ['notaris2', 'Bambang Hartono', Role::Notaris],
                ['notaris3', 'Ayu Pratiwi Handayani', Role::Notaris],
                ['staf', 'Dimas Prasetyo', Role::Staf],
                ['staf2', 'Sari Wulandari', Role::Staf],
                ['keuangan', 'Lina Marlina', Role::Keuangan],
                ['arsip', 'Agus Setiawan', Role::Arsiparis],
                ['admin', 'Admin TI', Role::Admin],
            ] as [$kunci, $nama, $role]) {
                $this->u[$kunci] = User::updateOrCreate(['email' => "{$kunci}@notaria.demo"], ['name' => $nama, 'password' => $sandi, 'role' => $role, 'is_active' => true]);
            }
            // Akun tamu: peran notaris agar semua layar dapat dilihat, tetapi tanpa pengaturan, profil, dan AI.
            User::updateOrCreate(['email' => 'tamu@notaria.demo'], ['name' => 'Tamu (Data Contoh)', 'password' => str()->password(32), 'role' => Role::Notaris, 'is_active' => true])
                ->forceFill(['is_tamu' => true])->save();
            Auth::setUser($this->u['notaris']); // tanpa sesi: hanya untuk created_by dan jejak audit

            $tahun = today()->year;
            foreach ([["{$tahun}-12-24", 'Cuti bersama Natal'], ["{$tahun}-12-25", 'Hari Raya Natal'], [($tahun + 1).'-01-01', 'Tahun Baru Masehi']] as [$t, $n]) {
                HariLibur::firstOrCreate(['tanggal' => $t], ['nama' => $n]);
            }
            foreach ([['L-A1', 'Lemari tahan api A, rak 1 (Bandung)'], ['L-B1', 'Lemari tahan api B, rak 1 (Cimahi)'], ['L-C1', 'Lemari tahan api C, rak 1 (Ngamprah)']] as [$kode, $nama]) {
                LokasiArsip::create(['kode' => $kode, 'nama' => $nama]);
            }

            $this->layanan = JenisLayanan::query()->pluck('id', 'kode');
            $tplPath = app(TemplateBawaan::class)->aktaUmum('template/akta-umum-v1.docx');
            $this->template = TemplateAkta::create(['nama' => 'Akta Umum (contoh)', 'versi' => 1, 'path' => $tplPath, 'status' => StatusTemplate::Disetujui,
                'disetujui_oleh' => $this->u['notaris']->id, 'disetujui_at' => now(), 'catatan' => 'Template contoh bawaan aplikasi.']);

            $this->kantorBandung();
            $this->kantorCimahi();
            $this->kantorBandungBarat();

            app(KalkulatorTenggat::class)->buatBulanan(today()->subMonthNoOverflow()->startOfMonth());
            // Kantor contoh yang tertib: tenggat lama sudah diselesaikan, hanya beberapa yang baru lewat tetap terlihat terlambat.
            Tenggat::query()->whereNull('selesai_at')->whereDate('jatuh_tempo', '<', today()->subDays(3))
                ->update(['selesai_at' => now(), 'selesai_oleh' => $this->u['staf']->id]);
            Auth::forgetUser();
        });

        $this->command?->info('Data contoh 3 notaris siap. Masuk di /app dengan notaris@, notaris2@, notaris3@, staf@, staf2@, keuangan@, arsip@, atau admin@notaria.demo (sandi: NOTARIA_DEMO_PASSWORD), atau tombol "Masuk sebagai tamu".');
    }

    /** Notaris dan PPAT di Kota Bandung: korporasi, fidusia, perjanjian. */
    private function kantorBandung(): void
    {
        $j = $this->jabatan('notaris', [
            'nama' => 'Rina Kartika', 'nama_lengkap' => 'Doktor RINA KARTIKA, Sarjana Hukum, Magister Kenotariatan',
            'nomor_sk' => 'AHU-00123.AH.02.01.Tahun 2015', 'tanggal_sk' => '2015-03-02', 'tanggal_sumpah' => '2015-04-10', 'tanggal_lahir' => '1978-06-14',
            'tempat_kedudukan' => 'Kota Bandung', 'alamat_kantor' => 'Jalan Contoh Raya Nomor 10, Bandung 40115 (alamat fiktif)',
            'kode' => 'RK', 'is_ppat' => true, 'daerah_kerja_ppat' => 'Kota Bandung',
        ]);
        $o = $this->orangFor('Kota Bandung');
        $saksi = [
            $o(['nama' => 'Dimas Prasetyo', 'nik' => '3273011203950001', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '1995-03-12', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Karyawan swasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Melati Nomor 3', 'rt_rw' => '002/005', 'kelurahan' => 'Cihapit', 'kecamatan' => 'Bandung Wetan', 'saksi_kantor' => true]),
            $o(['nama' => 'Sari Wulandari', 'nik' => '3273015507970002', 'tempat_lahir' => 'Garut', 'tanggal_lahir' => '1997-07-15', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Karyawan swasta', 'status_perkawinan' => 'belum_kawin', 'alamat' => 'Jalan Kenanga Nomor 8', 'rt_rw' => '001/003', 'kelurahan' => 'Lebakgede', 'kecamatan' => 'Coblong', 'saksi_kantor' => true]),
        ];
        $budi = $o(['nama' => 'Budi Santoso', 'nik' => '3273022001800003', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '1980-01-20', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Wiraswasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Merdeka Nomor 21', 'rt_rw' => '004/002', 'kelurahan' => 'Babakan Ciamis', 'kecamatan' => 'Sumur Bandung', 'telepon' => '0812000001', 'persetujuan_ai' => true]);
        $ani = $o(['nama' => 'Ani Lestari', 'nik' => '3273026503850004', 'tempat_lahir' => 'Cimahi', 'tanggal_lahir' => '1985-03-25', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Wiraswasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Merdeka Nomor 21', 'rt_rw' => '004/002', 'kelurahan' => 'Babakan Ciamis', 'kecamatan' => 'Sumur Bandung']);
        $hendra = $o(['nama' => 'Hendra Wijaya', 'nik' => '3273031108750005', 'tempat_lahir' => 'Jakarta', 'tanggal_lahir' => '1975-08-11', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Karyawan BUMN', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Dago Nomor 100', 'rt_rw' => '003/007', 'kelurahan' => 'Dago', 'kecamatan' => 'Coblong']);
        $maya = $o(['nama' => 'Maya Puspita', 'nik' => '3273034409900006', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '1990-09-04', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Pegawai swasta', 'status_perkawinan' => 'belum_kawin', 'alamat' => 'Jalan Riau Nomor 5', 'rt_rw' => '002/001', 'kelurahan' => 'Citarum', 'kecamatan' => 'Bandung Wetan']);
        $bank = $this->badan(['nama' => 'PT Bank Contoh Sejahtera Tbk', 'bentuk_badan' => 'pt', 'nomor_sk_badan' => 'AHU-0001.AH.01.01.Tahun 2010', 'nib' => '9120000000001', 'alamat' => 'Jalan Asia Afrika Nomor 1', 'kota' => 'Kota Bandung']);
        $budi->relasi()->create(['terkait_id' => $ani->id, 'hubungan' => 'suami_istri']);

        $b = $this->berkas($j, 'PERJANJIAN', 'Sewa ruko Jalan Merdeka', $budi, StatusBerkas::Selesai, 52, 'staf', ['nilai_transaksi' => 240_000_000]);
        $this->nomori($j, $this->akta($j, $b, 'Perjanjian Sewa Menyewa', $saksi, [[$budi], [$hendra]], 240_000_000), 45, '10:00');
        $b = $this->berkas($j, 'KUASA', 'Kuasa menjual tanah Cimahi', $maya, StatusBerkas::Selesai, 36, 'staf');
        $this->nomori($j, $this->akta($j, $b, 'Kuasa', $saksi, [[$maya]]), 33, '13:30');
        $b = $this->berkas($j, 'FIDUSIA', 'Fidusia kendaraan CV Maju', $bank, StatusBerkas::PascaAkta, 24, 'staf', ['nilai_transaksi' => 350_000_000]);
        $this->nomori($j, $this->akta($j, $b, 'Jaminan Fidusia', $saksi, [[$hendra, Kedudukan::MewakiliBadan, $bank, 'selaku kuasa Direksi berdasarkan Surat Kuasa Nomor 12/SK/2026'], [$budi]], 350_000_000), 20, '09:00');
        $ppjb = $this->berkas($j, 'PERJANJIAN', 'PPJB rumah Dago', $hendra, StatusBerkas::Ditandatangani, 15, 'staf', ['nilai_transaksi' => 1_200_000_000]);
        $this->nomori($j, $this->akta($j, $ppjb, 'Perjanjian Pengikatan Jual Beli', $saksi, [[$hendra], [$maya]], 1_200_000_000), 9, '11:00');

        // Berkas aktif: pendirian PT dijadwalkan besok, wasiat yang masih mengumpulkan dokumen (sangat rahasia).
        $pt = $this->berkas($j, 'PT-DIRI', 'Pendirian PT Sinar Kopi Priangan', $budi, StatusBerkas::Dijadwalkan, 6, 'staf');
        $a = $this->akta($j, $pt, 'Pendirian Perseroan Terbatas PT Sinar Kopi Priangan', $saksi, [[$budi], [$ani]], 100_000_000);
        $a->update(['status' => StatusAkta::Dijadwalkan, 'jadwal_at' => today()->addDay()->setTime(10, 0)]);
        app(PenyusunDraf::class)->buat($a, $this->template);
        $this->berkas($j, 'WASIAT', 'Wasiat Ibu Maya', $maya, StatusBerkas::Dokumen, 2, 'staf', ['sangat_rahasia' => true]);

        $surat = app(PenomoranSurat::class);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'legalisasi', 'tanggal' => $this->hari(18), 'judul' => 'Surat Pernyataan Ahli Waris', 'para_pihak' => "Budi Santoso\nAni Lestari", 'jumlah_lembar' => 2]);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'waarmerking', 'tanggal' => $this->hari(12), 'tanggal_surat' => $this->hari(23), 'judul' => 'Perjanjian Kerja Sama Distribusi', 'para_pihak' => "Hendra Wijaya\nMaya Puspita", 'jumlah_lembar' => 5]);

        // Keuangan: tagihan PPJB terbit dan dibayar sebagian.
        $t = $this->tagihan($ppjb, 9, [
            ['jenis' => 'honorarium', 'uraian' => 'Honorarium akta PPJB', 'jumlah' => 7_500_000],
            ['jenis' => 'titipan', 'uraian' => 'Pengecekan sertipikat di Kantor Pertanahan', 'jumlah' => 150_000],
        ]);
        $t->pembayaran()->create(['tanggal' => $this->hari(8), 'jumlah' => 5_000_000, 'metode' => 'transfer']);
        $ppjb->danaTitipan()->create(['tanggal' => $this->hari(8), 'arah' => 'masuk', 'jumlah' => 150_000, 'uraian' => 'Titipan biaya pengecekan sertipikat']);
    }

    /** Notaris di Kota Cimahi: badan usaha, yayasan, perjanjian kawin, wasiat. */
    private function kantorCimahi(): void
    {
        $j = $this->jabatan('notaris2', [
            'nama' => 'Bambang Hartono', 'nama_lengkap' => 'BAMBANG HARTONO, Sarjana Hukum, Magister Kenotariatan',
            'nomor_sk' => 'AHU-00456.AH.02.01.Tahun 2012', 'tanggal_sk' => '2012-08-20', 'tanggal_sumpah' => '2012-09-27', 'tanggal_lahir' => '1970-02-03',
            'tempat_kedudukan' => 'Kota Cimahi', 'alamat_kantor' => 'Jalan Contoh Amir Machmud Nomor 45, Cimahi 40525 (alamat fiktif)',
            'kode' => 'BH', 'is_ppat' => false,
        ]);
        $o = $this->orangFor('Kota Cimahi');
        $saksi = [
            $o(['nama' => 'Fajar Nugraha', 'nik' => '3277010505960007', 'tempat_lahir' => 'Cimahi', 'tanggal_lahir' => '1996-05-05', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Karyawan swasta', 'status_perkawinan' => 'belum_kawin', 'alamat' => 'Jalan Sriwijaya Nomor 7', 'rt_rw' => '003/004', 'kelurahan' => 'Setiamanah', 'kecamatan' => 'Cimahi Tengah', 'saksi_kantor' => true]),
            $o(['nama' => 'Intan Permata', 'nik' => '3277015912980008', 'tempat_lahir' => 'Sumedang', 'tanggal_lahir' => '1998-12-19', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Karyawan swasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Kebon Kopi Nomor 12', 'rt_rw' => '005/002', 'kelurahan' => 'Cibeureum', 'kecamatan' => 'Cimahi Selatan', 'saksi_kantor' => true]),
        ];
        $rudi = $o(['nama' => 'Rudi Hermawan', 'nik' => '3277021407820009', 'tempat_lahir' => 'Cimahi', 'tanggal_lahir' => '1982-07-14', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Wiraswasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Gatot Subroto Nomor 30', 'rt_rw' => '001/006', 'kelurahan' => 'Baros', 'kecamatan' => 'Cimahi Tengah', 'persetujuan_ai' => true]);
        $wawan = $o(['nama' => 'Wawan Setiawan', 'nik' => '3277020303790010', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '1979-03-03', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Petani', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Cihanjuang Nomor 88', 'rt_rw' => '002/009', 'kelurahan' => 'Cibabat', 'kecamatan' => 'Cimahi Utara']);
        $siti = $o(['nama' => 'Hj. Siti Aminah', 'nik' => '3277024102580011', 'tempat_lahir' => 'Cianjur', 'tanggal_lahir' => '1958-02-01', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Pensiunan guru', 'status_perkawinan' => 'cerai_mati', 'alamat' => 'Jalan Encep Kartawiria Nomor 4', 'rt_rw' => '004/001', 'kelurahan' => 'Citeureup', 'kecamatan' => 'Cimahi Utara']);
        $andi = $o(['nama' => 'Andi Saputra', 'nik' => '3277031010930012', 'tempat_lahir' => 'Cimahi', 'tanggal_lahir' => '1993-10-10', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Aparatur sipil negara', 'status_perkawinan' => 'belum_kawin', 'alamat' => 'Jalan Pesantren Nomor 15', 'rt_rw' => '006/003', 'kelurahan' => 'Cibabat', 'kecamatan' => 'Cimahi Utara']);
        $dewi = $o(['nama' => 'Dewi Anggraini', 'nik' => '3277036206950013', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '1995-06-22', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Dokter', 'status_perkawinan' => 'belum_kawin', 'alamat' => 'Jalan Cisangkan Nomor 9', 'rt_rw' => '002/002', 'kelurahan' => 'Padasuka', 'kecamatan' => 'Cimahi Tengah']);

        $b = $this->berkas($j, 'CV-DIRI', 'Pendirian CV Berkah Tani Mandiri', $rudi, StatusBerkas::Selesai, 47, 'staf2');
        $this->nomori($j, $this->akta($j, $b, 'Pendirian Persekutuan Komanditer CV Berkah Tani Mandiri', $saksi, [[$rudi], [$wawan]], 50_000_000), 40, '10:30');
        $cv = $b;
        $b = $this->berkas($j, 'YYS-DIRI', 'Pendirian Yayasan Pendidikan Cahaya Cimahi', $siti, StatusBerkas::PascaAkta, 31, 'staf2');
        $this->nomori($j, $this->akta($j, $b, 'Pendirian Yayasan Pendidikan Cahaya Cimahi', $saksi, [[$siti], [$rudi]]), 26, '09:30');
        $b = $this->berkas($j, 'PERJ-KAWIN', 'Perjanjian kawin Andi dan Dewi', $andi, StatusBerkas::Selesai, 18, 'staf2');
        $this->nomori($j, $this->akta($j, $b, 'Perjanjian Perkawinan', $saksi, [[$andi], [$dewi]]), 14, '15:00');
        $b = $this->berkas($j, 'WASIAT', 'Wasiat Hj. Siti Aminah', $siti, StatusBerkas::Selesai, 8, 'staf2', ['sangat_rahasia' => true]);
        $this->nomori($j, $this->akta($j, $b, 'Wasiat', $saksi, [[$siti]]), 5, '10:00');

        // Dijadwalkan hari ini pukul 14.00.
        $utang = $this->berkas($j, 'UTANG', 'Pengakuan utang Wawan kepada Rudi', $rudi, StatusBerkas::Dijadwalkan, 3, 'staf2', ['nilai_transaksi' => 75_000_000]);
        $a = $this->akta($j, $utang, 'Pengakuan Utang', $saksi, [[$wawan], [$rudi]], 75_000_000);
        $a->update(['status' => StatusAkta::Dijadwalkan, 'jadwal_at' => today()->setTime(14, 0)]);
        app(PenyusunDraf::class)->buat($a, $this->template);

        $surat = app(PenomoranSurat::class);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'legalisasi', 'tanggal' => $this->hari(10), 'judul' => 'Surat Perjanjian Jual Beli Hasil Panen', 'para_pihak' => "Wawan Setiawan\nRudi Hermawan", 'jumlah_lembar' => 3]);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'fotokopi', 'tanggal' => $this->hari(4), 'judul' => 'Ijazah dan transkrip nilai', 'para_pihak' => 'Andi Saputra', 'jumlah_lembar' => 4]);

        // Keuangan: tagihan pendirian CV lunas.
        $t = $this->tagihan($cv, 40, [
            ['jenis' => 'honorarium', 'uraian' => 'Honorarium akta pendirian CV', 'jumlah' => 3_500_000],
            ['jenis' => 'titipan', 'uraian' => 'Pendaftaran SABU dan NIB', 'jumlah' => 500_000],
        ]);
        $t->pembayaran()->create(['tanggal' => $this->hari(38), 'jumlah' => 4_000_000, 'metode' => 'transfer']);
    }

    /** Notaris dan PPAT di Kabupaten Bandung Barat: PPJB tanah, waris, kuasa; satu akta memperlihatkan larangan saksi keluarga. */
    private function kantorBandungBarat(): void
    {
        $j = $this->jabatan('notaris3', [
            'nama' => 'Ayu Pratiwi Handayani', 'nama_lengkap' => 'AYU PRATIWI HANDAYANI, Sarjana Hukum, Magister Kenotariatan',
            'nomor_sk' => 'AHU-00789.AH.02.01.Tahun 2019', 'tanggal_sk' => '2019-11-11', 'tanggal_sumpah' => '2019-12-16', 'tanggal_lahir' => '1988-04-21',
            'tempat_kedudukan' => 'Kabupaten Bandung Barat', 'alamat_kantor' => 'Jalan Contoh Raya Padalarang Nomor 77, Ngamprah 40552 (alamat fiktif)',
            'kode' => 'AP', 'is_ppat' => true, 'daerah_kerja_ppat' => 'Kabupaten Bandung Barat',
        ]);
        $o = $this->orangFor('Kabupaten Bandung Barat');
        $saksi = [
            $o(['nama' => 'Yoga Pratama', 'nik' => '3217011708970014', 'tempat_lahir' => 'Padalarang', 'tanggal_lahir' => '1997-08-17', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Karyawan swasta', 'status_perkawinan' => 'belum_kawin', 'alamat' => 'Jalan Raya Ngamprah Nomor 2', 'rt_rw' => '001/001', 'kelurahan' => 'Ngamprah', 'kecamatan' => 'Ngamprah', 'saksi_kantor' => true]),
            $o(['nama' => 'Nadia Rahma', 'nik' => '3217016801990015', 'tempat_lahir' => 'Lembang', 'tanggal_lahir' => '1999-01-28', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Karyawan swasta', 'status_perkawinan' => 'belum_kawin', 'alamat' => 'Jalan Grand Hotel Nomor 19', 'rt_rw' => '003/002', 'kelurahan' => 'Lembang', 'kecamatan' => 'Lembang', 'saksi_kantor' => true]),
        ];
        $asep = $o(['nama' => 'Asep Sunandar', 'nik' => '3217020909760016', 'tempat_lahir' => 'Lembang', 'tanggal_lahir' => '1976-09-09', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Petani', 'status_perkawinan' => 'kawin', 'alamat' => 'Kampung Cibodas RT 02', 'rt_rw' => '002/004', 'kelurahan' => 'Cibodas', 'kecamatan' => 'Lembang']);
        $euis = $o(['nama' => 'Euis Komalasari', 'nik' => '3217025511800017', 'tempat_lahir' => 'Lembang', 'tanggal_lahir' => '1980-11-15', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Mengurus rumah tangga', 'status_perkawinan' => 'kawin', 'alamat' => 'Kampung Cibodas RT 02', 'rt_rw' => '002/004', 'kelurahan' => 'Cibodas', 'kecamatan' => 'Lembang']);
        $taufik = $o(['nama' => 'Taufik Hidayat', 'nik' => '3273042512840018', 'tempat_lahir' => 'Bandung', 'tanggal_lahir' => '1984-12-25', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Pegawai swasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Setiabudi Nomor 210', 'rt_rw' => '005/003', 'kelurahan' => 'Isola', 'kecamatan' => 'Sukasari', 'kota' => 'Kota Bandung', 'telepon' => '0812000018', 'persetujuan_ai' => true]);
        $rini = $o(['nama' => 'Rini Susanti', 'nik' => '3217034403870019', 'tempat_lahir' => 'Cililin', 'tanggal_lahir' => '1987-03-04', 'jenis_kelamin' => 'P', 'pekerjaan' => 'Guru', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Raya Cililin Nomor 50', 'rt_rw' => '001/005', 'kelurahan' => 'Cililin', 'kecamatan' => 'Cililin']);
        $dadan = $o(['nama' => 'Dadan Ramdani', 'nik' => '3217031506900020', 'tempat_lahir' => 'Cililin', 'tanggal_lahir' => '1990-06-15', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Wiraswasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Raya Cililin Nomor 52', 'rt_rw' => '001/005', 'kelurahan' => 'Cililin', 'kecamatan' => 'Cililin']);
        $fikri = $o(['nama' => 'Fikri Rahman', 'nik' => '3217010202940021', 'tempat_lahir' => 'Lembang', 'tanggal_lahir' => '1994-02-02', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Wiraswasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Grand Hotel Nomor 19', 'rt_rw' => '003/002', 'kelurahan' => 'Lembang', 'kecamatan' => 'Lembang']);
        $asep->relasi()->create(['terkait_id' => $euis->id, 'hubungan' => 'suami_istri']);
        $rini->relasi()->create(['terkait_id' => $dadan->id, 'hubungan' => 'saudara']);
        $saksi[1]->relasi()->create(['terkait_id' => $fikri->id, 'hubungan' => 'saudara', 'catatan' => 'Contoh untuk memperlihatkan temuan R-04.']);

        $ppjb = $this->berkas($j, 'PERJANJIAN', 'PPJB tanah kebun Lembang', $taufik, StatusBerkas::PascaAkta, 44, 'staf', ['nilai_transaksi' => 450_000_000]);
        $this->nomori($j, $this->akta($j, $ppjb, 'Perjanjian Pengikatan Jual Beli', $saksi, [[$asep], [$euis], [$taufik]], 450_000_000), 38, '10:00');
        $b = $this->berkas($j, 'WARIS', 'Keterangan waris almarhum H. Dadang', $rini, StatusBerkas::Selesai, 28, 'staf');
        $this->nomori($j, $this->akta($j, $b, 'Keterangan Hak Waris', $saksi, [[$rini], [$dadan]]), 22, '13:00');
        $b = $this->berkas($j, 'KUASA', 'Kuasa mengurus sertipikat Cibodas', $euis, StatusBerkas::Selesai, 10, 'staf');
        $this->nomori($j, $this->akta($j, $b, 'Kuasa', $saksi, [[$euis]]), 8, '09:00');

        // Dijadwalkan besok: saksi kantor bersaudara dengan penghadap, sehingga pemeriksaan kesiapan menolak penomoran.
        $kerja = $this->berkas($j, 'PERJANJIAN', 'Kerja sama usaha kafe Lembang', $fikri, StatusBerkas::Dijadwalkan, 4, 'staf', ['nilai_transaksi' => 200_000_000]);
        $a = $this->akta($j, $kerja, 'Perjanjian Kerja Sama Usaha', $saksi, [[$fikri], [$taufik]], 200_000_000);
        $a->update(['status' => StatusAkta::Dijadwalkan, 'jadwal_at' => today()->addDay()->setTime(13, 0)]);
        app(PenyusunDraf::class)->buat($a, $this->template);

        $surat = app(PenomoranSurat::class);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'legalisasi', 'tanggal' => $this->hari(6), 'judul' => 'Surat Pernyataan Penguasaan Fisik Bidang Tanah', 'para_pihak' => 'Asep Sunandar', 'jumlah_lembar' => 1]);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'waarmerking', 'tanggal' => $this->hari(3), 'tanggal_surat' => $this->hari(30), 'judul' => 'Perjanjian Sewa Lahan Parkir', 'para_pihak' => "Fikri Rahman\nTaufik Hidayat", 'jumlah_lembar' => 2]);

        // Keuangan: tagihan PPJB belum dibayar.
        $this->tagihan($ppjb, 38, [
            ['jenis' => 'honorarium', 'uraian' => 'Honorarium akta PPJB tanah', 'jumlah' => 4_500_000],
            ['jenis' => 'titipan', 'uraian' => 'Pengecekan sertipikat dan ZNT', 'jumlah' => 350_000],
        ]);
    }

    private function jabatan(string $user, array $data): Jabatan
    {
        return Jabatan::create($data + ['jenis' => 'notaris', 'provinsi' => 'Jawa Barat', 'user_id' => $this->u[$user]->id, 'is_active' => true]);
    }

    /** @return \Closure(array): Subjek */
    private function orangFor(string $kota): \Closure
    {
        return fn (array $a) => Subjek::create($a + ['jenis' => 'orang', 'jenis_identitas' => 'ktp', 'kewarganegaraan' => 'Indonesia', 'kota' => $kota, 'provinsi' => 'Jawa Barat', 'catatan' => self::LABEL]);
    }

    private function badan(array $a): Subjek
    {
        return Subjek::create($a + ['jenis' => 'badan', 'provinsi' => 'Jawa Barat', 'catatan' => self::LABEL]);
    }

    private function berkas(Jabatan $j, string $kode, string $judul, Subjek $klien, StatusBerkas $status, int $hariLalu, string $pic, array $lain = []): Berkas
    {
        return Berkas::create($lain + [
            'judul' => $judul, 'jenis_layanan_id' => $this->layanan[$kode], 'jabatan_id' => $j->id, 'klien_id' => $klien->id,
            'pic_id' => $this->u[$pic]->id, 'status' => $status->value, 'tanggal_masuk' => $this->hari($hariLalu), 'catatan' => self::LABEL,
        ]);
    }

    /** @param  list<array{0: Subjek, 1?: Kedudukan, 2?: Subjek|null, 3?: string|null}>  $penghadap */
    private function akta(Jabatan $j, Berkas $b, string $judul, array $saksi, array $penghadap, ?float $nilai = null): Akta
    {
        $a = Akta::create(['berkas_id' => $b->id, 'judul' => $judul, 'jenis_layanan_id' => $b->jenis_layanan_id, 'status' => StatusAkta::Draf,
            'tempat' => 'Kantor Notaris', 'kota_tanda_tangan' => $j->tempat_kedudukan, 'provinsi_tanda_tangan' => 'Jawa Barat', 'nilai_ekonomis' => $nilai]);
        foreach ($penghadap as $i => $p) {
            $a->penghadap()->create(['subjek_id' => $p[0]->id, 'kedudukan' => $p[1] ?? Kedudukan::DiriSendiri, 'mewakili_id' => ($p[2] ?? null)?->id,
                'keterangan_kedudukan' => $p[3] ?? null, 'urutan' => $i + 1]);
        }
        foreach ($saksi as $s) {
            $a->saksi()->create(['subjek_id' => $s->id, 'jenis' => 'akta']);
        }

        return $a;
    }

    private function nomori(Jabatan $j, Akta $a, int $hariLalu, string $jam): void
    {
        $notaris = $j->user;
        Auth::setUser($notaris);
        app(PenyusunDraf::class)->buat($a, $this->template)->update(['final' => true]);
        app(PenomoranAkta::class)->beriNomor($a, today()->subDays($hariLalu), $jam, $notaris, ['dibacakan' => true]);
        Auth::setUser($this->u['notaris']);
    }

    private function tagihan(Berkas $b, int $hariLalu, array $items): Tagihan
    {
        $t = Tagihan::create(['berkas_id' => $b->id, 'tanggal' => $this->hari($hariLalu), 'jatuh_tempo' => today()->subDays($hariLalu)->addDays(14)->toDateString(),
            'status' => StatusTagihan::Draf, 'catatan' => self::LABEL]);
        $t->items()->createMany($items);
        $t->update(['status' => StatusTagihan::Terbit]);

        return $t;
    }

    private function hari(int $lalu): string
    {
        return today()->subDays($lalu)->toDateString();
    }
}
