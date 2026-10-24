<?php

namespace Database\Seeders;

use App\Enums\Kedudukan;
use App\Enums\Role;
use App\Enums\StatusAkta;
use App\Enums\StatusBerkas;
use App\Enums\StatusTagihan;
use App\Enums\StatusTemplate;
use App\Models\AiPermintaan;
use App\Models\Akta;
use App\Models\BeritaAcaraPembetulan;
use App\Models\Berkas;
use App\Models\BerkasPihak;
use App\Models\BukuFisik;
use App\Models\Dokumen;
use App\Models\HariLibur;
use App\Models\Jabatan;
use App\Models\JenisLayanan;
use App\Models\LokasiArsip;
use App\Models\PeminjamanMinuta;
use App\Models\Pengaturan;
use App\Models\PermintaanPenegakHukum;
use App\Models\Salinan;
use App\Models\SerahTerimaProtokol;
use App\Models\Subjek;
use App\Models\Tagihan;
use App\Models\TandaTerima;
use App\Models\TemplateAkta;
use App\Models\Tenggat;
use App\Models\Tugas;
use App\Models\User;
use App\Services\KalkulatorTenggat;
use App\Services\PenjilidanMinuta;
use App\Services\PenomoranAkta;
use App\Services\PenomoranSurat;
use App\Services\PenyusunDraf;
use App\Services\TemplateBawaan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

    /** @var array<string, int> kode jabatan => id lokasi arsip */
    private array $lokasi = [];

    /** Peran para pihak di berkas menurut jenis layanan, berurutan seperti penghadap akta. */
    private const PERAN = [
        'PERJANJIAN' => ['Pihak pertama', 'Pihak kedua', 'Pihak ketiga'], 'KUASA' => ['Pemberi kuasa'], 'FIDUSIA' => ['Penerima fidusia', 'Pemberi fidusia'],
        'PT-DIRI' => ['Pendiri', 'Pendiri'], 'PT-UBAH' => ['Direktur', 'Komisaris'], 'CV-DIRI' => ['Sekutu aktif', 'Sekutu komanditer'], 'YYS-DIRI' => ['Pendiri', 'Pendiri'],
        'PKM-DIRI' => ['Pendiri', 'Pendiri', 'Pendiri'], 'PERJ-KAWIN' => ['Calon suami', 'Calon istri'], 'WASIAT' => ['Pewasiat'], 'UTANG' => ['Debitur', 'Kreditur'],
        'WARIS' => ['Ahli waris', 'Ahli waris'], 'PROTES' => ['Pemegang cek'], 'SKMHT' => ['Pemberi kuasa', 'Penerima kuasa'],
    ];

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
            // Akun tamu: peran Tamu melihat semua layar tanpa dapat mengubah data apa pun.
            User::updateOrCreate(['email' => 'tamu@notaria.demo'], ['name' => 'Tamu (Data Contoh)', 'password' => str()->password(32), 'role' => Role::Tamu, 'is_active' => true])
                ->forceFill(['is_tamu' => true])->save();
            Auth::setUser($this->u['notaris']); // tanpa sesi: hanya untuk created_by dan jejak audit

            $tahun = today()->year;
            foreach ([["{$tahun}-12-24", 'Cuti bersama Natal'], ["{$tahun}-12-25", 'Hari Raya Natal'], [($tahun + 1).'-01-01', 'Tahun Baru Masehi']] as [$t, $n]) {
                HariLibur::firstOrCreate(['tanggal' => $t], ['nama' => $n]);
            }
            foreach ([['RK', 'L-A1', 'Lemari tahan api A, rak 1 (Bandung)'], ['BH', 'L-B1', 'Lemari tahan api B, rak 1 (Cimahi)'], ['AP', 'L-C1', 'Lemari tahan api C, rak 1 (Ngamprah)']] as [$jab, $kode, $nama]) {
                $this->lokasi[$jab] = LokasiArsip::create(['kode' => $kode, 'nama' => $nama])->id;
            }
            LokasiArsip::create(['kode' => 'G-01', 'nama' => 'Gudang arsip protokol lama (Bandung)']);

            $this->layanan = JenisLayanan::query()->pluck('id', 'kode');
            $tplPath = app(TemplateBawaan::class)->aktaUmum('template/akta-umum-v1.docx');
            $this->template = TemplateAkta::create(['nama' => 'Akta Umum (contoh)', 'versi' => 1, 'path' => $tplPath, 'status' => StatusTemplate::Disetujui,
                'disetujui_oleh' => $this->u['notaris']->id, 'disetujui_at' => now(), 'catatan' => 'Template contoh bawaan aplikasi.']);
            TemplateAkta::create(['nama' => 'Akta Umum (contoh)', 'versi' => 2, 'path' => app(TemplateBawaan::class)->aktaUmum('template/akta-umum-v2.docx'),
                'status' => StatusTemplate::Draf, 'catatan' => 'Versi 2 menunggu persetujuan notaris: redaksi komparisi badan hukum diperbarui.']);

            $this->kantorBandung();
            $this->kantorCimahi();
            $this->kantorBandungBarat();

            app(KalkulatorTenggat::class)->buatBulanan(today()->subMonthNoOverflow()->startOfMonth());
            // Kantor contoh yang tertib: tenggat lama sudah diselesaikan, hanya yang baru lewat tetap terlihat terlambat.
            Tenggat::query()->whereNull('selesai_at')->whereDate('jatuh_tempo', '<', today()->subDays(7))
                ->update(['selesai_at' => now(), 'selesai_oleh' => $this->u['staf']->id, 'catatan' => 'Selesai (data contoh).']);

            $this->lengkapiBerkas();
            $this->lengkapiProtokol();
            $this->logAi();
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
        $sewa = $this->nomori($j, $this->akta($j, $b, 'Perjanjian Sewa Menyewa', $saksi, [[$budi], [$hendra]], 240_000_000), 45, '10:00');
        $b = $this->berkas($j, 'KUASA', 'Kuasa menjual tanah Cimahi', $maya, StatusBerkas::Selesai, 36, 'staf');
        $kuasa = $this->nomori($j, $this->akta($j, $b, 'Kuasa', $saksi, [[$maya]]), 33, '13:30');
        $b = $this->berkas($j, 'PROTES', 'Protes non-pembayaran cek Hendra', $hendra, StatusBerkas::Selesai, 28, 'staf');
        $this->nomori($j, $this->akta($j, $b, 'Protes Non-Pembayaran Cek', $saksi, [[$hendra]], 85_000_000), 27, '15:30');
        $fid = $this->berkas($j, 'FIDUSIA', 'Fidusia kendaraan CV Maju', $bank, StatusBerkas::PascaAkta, 24, 'staf', ['nilai_transaksi' => 350_000_000]);
        $fidusia = $this->nomori($j, $this->akta($j, $fid, 'Jaminan Fidusia', $saksi, [[$hendra, Kedudukan::MewakiliBadan, $bank, 'selaku kuasa Direksi berdasarkan Surat Kuasa Nomor 12/SK/2026'], [$budi]], 350_000_000), 20, '09:00');
        $ppjb = $this->berkas($j, 'PERJANJIAN', 'PPJB rumah Dago', $hendra, StatusBerkas::Ditandatangani, 15, 'staf', ['nilai_transaksi' => 1_200_000_000]);
        $aktaPpjb = $this->nomori($j, $this->akta($j, $ppjb, 'Perjanjian Pengikatan Jual Beli', $saksi, [[$hendra], [$maya]], 1_200_000_000), 9, '11:00',
            ['renvoi_tambah' => 1, 'renvoi_coret' => 2, 'catatan_penandatanganan' => 'Penghadap Maya Puspita membubuhkan cap jempol kanan selain tanda tangan.']);
        $b = $this->berkas($j, 'SKMHT', 'SKMHT rumah Jalan Merdeka untuk Bank Contoh', $budi, StatusBerkas::PascaAkta, 4, 'staf', ['nilai_transaksi' => 500_000_000]);
        $this->nomori($j, $this->akta($j, $b, 'Surat Kuasa Membebankan Hak Tanggungan', $saksi, [[$budi], [$ani]], 500_000_000), 2, '10:00');

        // Setelah tanda tangan: salinan, kutipan, grosse, satu permintaan salinan yang ditolak, pembetulan, peminjaman minuta.
        Salinan::create(['akta_id' => $aktaPpjb->id, 'jenis' => 'salinan', 'tanggal' => $this->hari(8), 'penerima' => 'Hendra Wijaya', 'dasar_hak' => 'pihak', 'bermeterai' => true]);
        Salinan::create(['akta_id' => $aktaPpjb->id, 'jenis' => 'salinan', 'tanggal' => $this->hari(8), 'penerima' => 'Maya Puspita', 'dasar_hak' => 'pihak', 'bermeterai' => true]);
        Salinan::create(['akta_id' => $aktaPpjb->id, 'jenis' => 'salinan', 'tanggal' => $this->hari(3), 'penerima' => 'Sdr. Joni (tetangga, bukan pihak)', 'dasar_hak' => 'tidak_berhak',
            'alasan_tolak' => 'Bukan pihak, ahli waris, atau penerima hak (Pasal 54 UUJN).']);
        Salinan::create(['akta_id' => $fidusia->id, 'jenis' => 'grosse', 'tanggal' => $this->hari(19), 'penerima' => 'PT Bank Contoh Sejahtera Tbk', 'dasar_hak' => 'penerima_hak', 'bermeterai' => true,
            'keterangan' => 'Grosse pertama untuk kreditur penerima fidusia.']);
        Salinan::create(['akta_id' => $kuasa->id, 'jenis' => 'kutipan', 'tanggal' => $this->hari(30), 'penerima' => 'Maya Puspita', 'dasar_hak' => 'pihak']);
        BeritaAcaraPembetulan::create(['akta_id' => $sewa->id, 'tanggal' => $this->hari(40), 'kesalahan' => 'Tertulis RT/RW pihak pertama 004/012.',
            'pembetulan' => 'Seharusnya RT/RW 004/002 sesuai KTP.', 'disampaikan_kepada' => "Budi Santoso\nHendra Wijaya"]);
        PeminjamanMinuta::create(['akta_id' => $kuasa->id, 'peminjam_id' => $this->u['staf']->id, 'alasan' => 'Pemindaian minuta untuk cadangan digital',
            'dipinjam_at' => today()->subDays(31)->setTime(9, 0), 'jatuh_tempo' => $this->hari(30), 'dikembalikan_at' => today()->subDays(30)->setTime(16, 0)]);
        PeminjamanMinuta::create(['akta_id' => $sewa->id, 'peminjam_id' => $this->u['staf2']->id, 'alasan' => 'Pembuatan salinan tambahan',
            'dipinjam_at' => today()->subDays(4)->setTime(10, 0), 'jatuh_tempo' => $this->hari(2)]);
        PermintaanPenegakHukum::create(['akta_id' => $kuasa->id, 'jenis' => 'fotokopi_minuta', 'instansi' => 'Polrestabes Bandung (contoh)', 'nomor_surat' => 'B/123/X/2026/Reskrim',
            'tanggal_surat' => $this->hari(16), 'nomor_persetujuan_mkn' => 'UM.MKNW.JB.11.26-45', 'tanggal_persetujuan_mkn' => $this->hari(6),
            'tanggal_pelaksanaan' => $this->hari(3), 'catatan' => 'Fotokopi minuta diserahkan dengan berita acara penyerahan; minuta tetap di kantor.']);

        // Berkas aktif: pendirian PT dijadwalkan besok, wasiat yang masih mengumpulkan dokumen (sangat rahasia).
        $pt = $this->berkas($j, 'PT-DIRI', 'Pendirian PT Sinar Kopi Priangan', $budi, StatusBerkas::Dijadwalkan, 6, 'staf');
        $a = $this->akta($j, $pt, 'Pendirian Perseroan Terbatas PT Sinar Kopi Priangan', $saksi, [[$budi], [$ani]], 100_000_000);
        $a->update(['status' => StatusAkta::Dijadwalkan, 'jadwal_at' => today()->addDay()->setTime(10, 0)]);
        app(PenyusunDraf::class)->buat($a, $this->template);
        $this->berkas($j, 'WASIAT', 'Wasiat Ibu Maya', $maya, StatusBerkas::Dokumen, 2, 'staf', ['sangat_rahasia' => true]);

        $surat = app(PenomoranSurat::class);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'legalisasi', 'tanggal' => $this->hari(18), 'judul' => 'Surat Pernyataan Ahli Waris', 'para_pihak' => "Budi Santoso\nAni Lestari", 'jumlah_lembar' => 2]);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'waarmerking', 'tanggal' => $this->hari(12), 'tanggal_surat' => $this->hari(23), 'judul' => 'Perjanjian Kerja Sama Distribusi', 'para_pihak' => "Hendra Wijaya\nMaya Puspita", 'jumlah_lembar' => 5]);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'legalisasi', 'tanggal' => $this->hari(5), 'judul' => 'Surat Kuasa Pengambilan BPKB', 'para_pihak' => 'Budi Santoso', 'jumlah_lembar' => 1]);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'fotokopi', 'tanggal' => $this->hari(1), 'judul' => 'Akta kelahiran dan kartu keluarga', 'para_pihak' => 'Maya Puspita', 'jumlah_lembar' => 3]);

        // Keuangan: tagihan PPJB terbit dan dibayar sebagian.
        $t = $this->tagihan($ppjb, 9, [
            ['jenis' => 'honorarium', 'uraian' => 'Honorarium akta PPJB', 'jumlah' => 7_500_000],
            ['jenis' => 'titipan', 'uraian' => 'Pengecekan sertipikat di Kantor Pertanahan', 'jumlah' => 150_000],
        ]);
        $t->pembayaran()->create(['tanggal' => $this->hari(8), 'jumlah' => 5_000_000, 'metode' => 'transfer']);
        $ppjb->danaTitipan()->create(['tanggal' => $this->hari(8), 'arah' => 'masuk', 'jumlah' => 150_000, 'uraian' => 'Titipan biaya pengecekan sertipikat']);
        $ppjb->danaTitipan()->create(['tanggal' => $this->hari(7), 'arah' => 'keluar', 'jumlah' => 150_000, 'uraian' => 'Dibayarkan ke Kantor Pertanahan Kota Bandung (PNBP pengecekan)']);
        TandaTerima::create(['berkas_id' => $ppjb->id, 'arah' => 'masuk', 'tanggal' => $this->hari(14), 'pihak' => 'Hendra Wijaya',
            'daftar_dokumen' => "Sertipikat Hak Milik No. 1234/Dago (asli)\nSPPT PBB 5 tahun terakhir (asli)\nIMB/PBG (fotokopi)"]);
        TandaTerima::create(['berkas_id' => $ppjb->id, 'arah' => 'keluar', 'tanggal' => $this->hari(8), 'pihak' => 'Maya Puspita', 'daftar_dokumen' => 'Salinan Akta Perjanjian Pengikatan Jual Beli (1 rangkap)']);

        // Fidusia untuk bank: tagihan lunas lewat QRIS, titipan PNBP pendaftaran fidusia.
        $t = $this->tagihan($fid, 20, [
            ['jenis' => 'honorarium', 'uraian' => 'Honorarium akta jaminan fidusia', 'jumlah' => 3_000_000],
            ['jenis' => 'titipan', 'uraian' => 'PNBP pendaftaran jaminan fidusia', 'jumlah' => 400_000],
        ]);
        $t->pembayaran()->create(['tanggal' => $this->hari(18), 'jumlah' => 3_400_000, 'metode' => 'qris']);
        $fid->danaTitipan()->create(['tanggal' => $this->hari(18), 'arah' => 'masuk', 'jumlah' => 400_000, 'uraian' => 'Titipan PNBP fidusia']);
        $fid->danaTitipan()->create(['tanggal' => $this->hari(17), 'arah' => 'keluar', 'jumlah' => 400_000, 'uraian' => 'Pembayaran PNBP fidusia online (bukti setor terlampir)']);

        // Dokumen identitas berkas PT: KTP para pendiri dan bukti pemesanan nama.
        $this->dokumen($budi, 'ktp', 'KTP Budi Santoso');
        $this->dokumen($ani, 'ktp', 'KTP Ani Lestari');
        $this->dokumen($pt, 'lainnya', 'Bukti pemesanan nama PT Sinar Kopi Priangan', today()->addDays(54)->toDateString());
        foreach ([['Pesan nama PT di AHU', 'staf', -5, true], ['Lengkapi data modal dan susunan saham', 'staf', -2, true], ['Kirim draf akta ke klien untuk dibaca', 'staf2', 0, false], ['Siapkan lembar tanda tangan dan sidik jari', 'staf', 1, false]] as [$judul, $pic, $hari, $selesai]) {
            Tugas::create(['berkas_id' => $pt->id, 'judul' => $judul, 'user_id' => $this->u[$pic]->id, 'jatuh_tempo' => today()->addDays($hari)->toDateString(),
                'selesai_at' => $selesai ? today()->addDays($hari)->setTime(15, 0) : null]);
        }
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
        $koperasi = $this->badan(['nama' => 'PT Berkah Tani Nusantara', 'bentuk_badan' => 'pt', 'nomor_sk_badan' => 'AHU-0099.AH.01.01.Tahun 2021', 'nib' => '9120000000099', 'alamat' => 'Jalan Raya Cibabat Nomor 120', 'kota' => 'Kota Cimahi']);
        $b = $this->berkas($j, 'PT-UBAH', 'RUPS PT Berkah Tani Nusantara: tambah modal', $koperasi, StatusBerkas::Selesai, 35, 'staf2', ['nilai_transaksi' => 2_000_000_000]);
        $this->nomori($j, $this->akta($j, $b, 'Pernyataan Keputusan Rapat Umum Pemegang Saham PT Berkah Tani Nusantara', $saksi,
            [[$rudi, Kedudukan::MewakiliBadan, $koperasi, 'selaku Direktur Utama berdasarkan notulen RUPS'], [$wawan, Kedudukan::MewakiliBadan, $koperasi, 'selaku Komisaris']]), 33, '11:00');
        $yys = $this->berkas($j, 'YYS-DIRI', 'Pendirian Yayasan Pendidikan Cahaya Cimahi', $siti, StatusBerkas::PascaAkta, 31, 'staf2');
        $yayasan = $this->nomori($j, $this->akta($j, $yys, 'Pendirian Yayasan Pendidikan Cahaya Cimahi', $saksi, [[$siti], [$rudi]]), 26, '09:30');
        Salinan::create(['akta_id' => $yayasan->id, 'jenis' => 'kutipan', 'tanggal' => $this->hari(25), 'penerima' => 'Hj. Siti Aminah (untuk pengajuan pengesahan)', 'dasar_hak' => 'pihak']);
        $b = $this->berkas($j, 'PERJ-KAWIN', 'Perjanjian kawin Andi dan Dewi', $andi, StatusBerkas::Selesai, 18, 'staf2');
        $this->nomori($j, $this->akta($j, $b, 'Perjanjian Perkawinan', $saksi, [[$andi], [$dewi]]), 14, '15:00');
        $b = $this->berkas($j, 'WASIAT', 'Wasiat Hj. Siti Aminah', $siti, StatusBerkas::Selesai, 8, 'staf2', ['sangat_rahasia' => true]);
        $this->nomori($j, $this->akta($j, $b, 'Wasiat', $saksi, [[$siti]]), 5, '10:00');

        // Dijadwalkan hari ini pukul 14.00.
        $utang = $this->berkas($j, 'UTANG', 'Pengakuan utang Wawan kepada Rudi', $rudi, StatusBerkas::Dijadwalkan, 3, 'staf2', ['nilai_transaksi' => 75_000_000]);
        $a = $this->akta($j, $utang, 'Pengakuan Utang', $saksi, [[$wawan], [$rudi]], 75_000_000);
        $a->update(['status' => StatusAkta::Dijadwalkan, 'jadwal_at' => today()->setTime(14, 0)]);
        app(PenyusunDraf::class)->buat($a, $this->template);

        // Dijadwalkan lusa: penghadap WNA dengan paspor yang sudah habis berlaku, sehingga kesiapan menandai R-14.
        $kenji = $o(['nama' => 'Kenji Tanaka', 'nik' => 'TZ1234567', 'jenis_identitas' => 'paspor', 'kewarganegaraan' => 'Jepang', 'identitas_berlaku_sampai' => $this->hari(12),
            'tempat_lahir' => 'Osaka', 'tanggal_lahir' => '1981-05-30', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Konsultan', 'status_perkawinan' => 'kawin',
            'alamat' => 'Apartemen Contoh Tower B Nomor 1203', 'rt_rw' => '001/001', 'kelurahan' => 'Baros', 'kecamatan' => 'Cimahi Tengah']);
        $gudang = $this->berkas($j, 'PERJANJIAN', 'Pinjam pakai gudang Cibabat', $koperasi, StatusBerkas::Dijadwalkan, 2, 'staf2', ['nilai_transaksi' => 120_000_000]);
        $a = $this->akta($j, $gudang, 'Perjanjian Pinjam Pakai Gudang', $saksi, [[$kenji], [$rudi, Kedudukan::MewakiliBadan, $koperasi, 'selaku Direktur Utama']], 120_000_000);
        $a->update(['status' => StatusAkta::Dijadwalkan, 'jadwal_at' => today()->addDays(2)->setTime(10, 30)]);
        app(PenyusunDraf::class)->buat($a, $this->template);
        $this->dokumen($kenji, 'paspor', 'Paspor Kenji Tanaka (habis berlaku)', $this->hari(12));

        $surat = app(PenomoranSurat::class);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'legalisasi', 'tanggal' => $this->hari(10), 'judul' => 'Surat Perjanjian Jual Beli Hasil Panen', 'para_pihak' => "Wawan Setiawan\nRudi Hermawan", 'jumlah_lembar' => 3]);
        $surat->catat(['jabatan_id' => $j->id, 'jenis' => 'fotokopi', 'tanggal' => $this->hari(4), 'judul' => 'Ijazah dan transkrip nilai', 'para_pihak' => 'Andi Saputra', 'jumlah_lembar' => 4]);

        // Keuangan: tagihan pendirian CV lunas.
        $t = $this->tagihan($cv, 40, [
            ['jenis' => 'honorarium', 'uraian' => 'Honorarium akta pendirian CV', 'jumlah' => 3_500_000],
            ['jenis' => 'titipan', 'uraian' => 'Pendaftaran SABU dan NIB', 'jumlah' => 500_000],
        ]);
        $t->pembayaran()->create(['tanggal' => $this->hari(38), 'jumlah' => 4_000_000, 'metode' => 'transfer']);
        $cv->danaTitipan()->create(['tanggal' => $this->hari(38), 'arah' => 'masuk', 'jumlah' => 500_000, 'uraian' => 'Titipan biaya pendaftaran SABU dan NIB']);
        $cv->danaTitipan()->create(['tanggal' => $this->hari(37), 'arah' => 'keluar', 'jumlah' => 500_000, 'uraian' => 'Pembayaran PNBP pendaftaran CV']);

        // Yayasan: tagihan dibayar tunai sebagian.
        $t = $this->tagihan($yys, 26, [
            ['jenis' => 'honorarium', 'uraian' => 'Honorarium akta pendirian yayasan', 'jumlah' => 4_000_000],
            ['jenis' => 'titipan', 'uraian' => 'PNBP pengesahan badan hukum yayasan', 'jumlah' => 1_000_000],
        ]);
        $t->pembayaran()->create(['tanggal' => $this->hari(25), 'jumlah' => 2_500_000, 'metode' => 'tunai']);
        TandaTerima::create(['berkas_id' => $yys->id, 'arah' => 'masuk', 'tanggal' => $this->hari(31), 'pihak' => 'Hj. Siti Aminah',
            'daftar_dokumen' => "KTP para pendiri (fotokopi)\nBukti pemesanan nama yayasan\nDaftar kekayaan awal yayasan"]);
        foreach ([['Ajukan pengesahan yayasan di AHU', 'staf2', 1, false], ['Serahkan SK pengesahan ke klien', 'staf2', 7, false]] as [$judul, $pic, $hari, $selesai]) {
            Tugas::create(['berkas_id' => $yys->id, 'judul' => $judul, 'user_id' => $this->u[$pic]->id, 'jatuh_tempo' => today()->addDays($hari)->toDateString()]);
        }
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
        $b = $this->berkas($j, 'PKM-DIRI', 'Pendirian Perkumpulan Petani Kopi Lembang', $asep, StatusBerkas::PascaAkta, 19, 'staf');
        $this->nomori($j, $this->akta($j, $b, 'Pendirian Perkumpulan Petani Kopi Lembang', $saksi, [[$asep], [$taufik], [$dadan]]), 15, '14:00');
        // Jasa hukum cuma-cuma (Pasal 37 UUJN) untuk warga kurang mampu.
        $b = $this->berkas($j, 'KUASA', 'Kuasa mengurus sertipikat Cibodas', $euis, StatusBerkas::Selesai, 10, 'staf', ['cuma_cuma' => true]);
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
        TandaTerima::create(['berkas_id' => $ppjb->id, 'arah' => 'masuk', 'tanggal' => $this->hari(42), 'pihak' => 'Asep Sunandar',
            'daftar_dokumen' => "Sertipikat Hak Milik No. 567/Cibodas (asli)\nSPPT PBB 2022-2026 (asli)\nKTP dan KK suami istri (fotokopi)"]);
        $this->dokumen($asep, 'sertipikat', 'SHM No. 567/Cibodas (pindaian)');
        $this->dokumen($taufik, 'ktp', 'KTP Taufik Hidayat');
        $this->dokumen($fikri, 'ktp', 'KTP Fikri Rahman');
        foreach ([['Ganti saksi Nadia Rahma (bersaudara dengan penghadap)', 'staf', 0, false], ['Konfirmasi jadwal dengan Taufik Hidayat', 'staf2', 0, true]] as [$judul, $pic, $hari, $selesai]) {
            Tugas::create(['berkas_id' => $kerja->id, 'judul' => $judul, 'user_id' => $this->u[$pic]->id, 'jatuh_tempo' => today()->addDays($hari)->toDateString(),
                'selesai_at' => $selesai ? now() : null, 'catatan' => $selesai ? null : 'Temuan R-04 pada pemeriksaan kesiapan (Pasal 40 UUJN).']);
        }

        // Cuti notaris: protokol diserahkan sementara kepada notaris pengganti, lalu kembali.
        SerahTerimaProtokol::create(['arah' => 'keluar', 'notaris_asal' => 'Ayu Pratiwi Handayani, S.H., M.Kn.', 'notaris_penerima' => 'Rahmat Hidayat, S.H. (notaris pengganti, fiktif)',
            'alasan' => 'cuti', 'tanggal' => today()->subMonths(5)->toDateString(), 'nomor_berita_acara' => 'BA-01/AP/'.today()->subMonths(5)->year,
            'tahun_awal' => 2020, 'tahun_akhir' => today()->subMonths(5)->year, 'jumlah_buku' => 14, 'ada_protokol_25_tahun' => false, 'catatan' => 'Cuti melahirkan tiga bulan. '.self::LABEL]);
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

    private function nomori(Jabatan $j, Akta $a, int $hariLalu, string $jam, array $hasil = []): Akta
    {
        $notaris = $j->user;
        Auth::setUser($notaris);
        app(PenyusunDraf::class)->buat($a, $this->template)->update(['final' => true]);
        app(PenomoranAkta::class)->beriNomor($a, today()->subDays($hariLalu), $jam, $notaris, $hasil + ['dibacakan' => true]);
        Auth::setUser($this->u['notaris']);

        return $a->refresh();
    }

    /** Pihak, daftar periksa dokumen sesuai tahap berkas, dan kelengkapan minuta. */
    private function lengkapiBerkas(): void
    {
        foreach (Berkas::with(['jenisLayanan', 'checklist', 'akta.penghadap'])->get() as $b) {
            $peran = self::PERAN[$b->jenisLayanan->kode] ?? ['Pihak'];
            $penghadap = $b->akta->flatMap->penghadap;
            if ($penghadap->isEmpty()) {
                BerkasPihak::create(['berkas_id' => $b->id, 'subjek_id' => $b->klien_id, 'peran' => $peran[0]]);
            }
            foreach ($penghadap->values() as $i => $p) {
                BerkasPihak::firstOrCreate(['berkas_id' => $b->id, 'subjek_id' => $p->mewakili_id ?? $p->subjek_id], ['peran' => $peran[$i] ?? end($peran)]);
            }

            $tahap = $b->status;
            foreach ($b->checklist->values() as $i => $c) {
                $status = match (true) {
                    in_array($tahap, [StatusBerkas::Selesai, StatusBerkas::PascaAkta, StatusBerkas::Ditandatangani], true) => 'diverifikasi',
                    $tahap === StatusBerkas::Dijadwalkan => $i === $b->checklist->count() - 1 ? 'diterima' : 'diverifikasi',
                    default => $i % 3 === 0 ? 'diterima' : 'diminta',
                };
                $c->update(['status' => $status]);
            }
        }

        // Minuta: halaman buku repertorium fisik dicatat, lampiran dan sidik jari lengkap kecuali akta terbaru tiap notaris.
        foreach (Jabatan::all() as $j) {
            $buku = BukuFisik::create(['jabatan_id' => $j->id, 'jenis_daftar' => 'repertorium', 'nomor_buku' => 'REP-'.today()->year.'/1', 'jumlah_halaman' => 200,
                'tanggal_paraf_mpd' => today()->startOfYear()->subDays(20)->toDateString(), 'mulai_dipakai' => today()->startOfYear()->toDateString(), 'aktif' => true]);
            BukuFisik::create(['jabatan_id' => $j->id, 'jenis_daftar' => 'klapper', 'nomor_buku' => 'KLP-'.today()->year.'/1', 'jumlah_halaman' => 100,
                'tanggal_paraf_mpd' => today()->startOfYear()->subDays(20)->toDateString(), 'mulai_dipakai' => today()->startOfYear()->toDateString(), 'aktif' => true]);
            $akta = Akta::query()->where('jabatan_id', $j->id)->bernomor()->orderBy('nomor_urut')->get();
            foreach ($akta as $i => $a) {
                $terbaru = $i === $akta->count() - 1;
                Akta::query()->whereKey($a->id)->update(['buku_fisik_id' => $terbaru ? null : $buku->id, 'halaman_buku' => $terbaru ? null : $a->nomor_urut + 40,
                    'minuta_lampiran_lengkap' => ! $terbaru, 'minuta_sidik_jari_lengkap' => ! $terbaru]);
            }
        }
    }

    /** Penjilidan minuta bulan lalu, buku daftar fisik lain, dan serah terima protokol notaris yang sudah meninggal. */
    private function lengkapiProtokol(): void
    {
        $lalu = today()->subMonthNoOverflow();
        foreach (Jabatan::all() as $j) {
            $jilid = rescue(fn () => app(PenjilidanMinuta::class)->susun($j->id, $lalu->year, $lalu->month, $this->lokasi[$j->kode] ?? null), [], false);
            if ($j->kode !== 'AP') {
                foreach ($jilid as $x) {
                    $x->update(['dijilid_at' => today()->startOfMonth()->addDays(min(4, today()->day - 1))->setTime(14, 0)]);
                }
            }
        }

        SerahTerimaProtokol::create(['arah' => 'masuk', 'notaris_asal' => 'R. Soetisna Wiradinata, S.H. (almarhum, fiktif)', 'notaris_penerima' => 'Dr. Rina Kartika, S.H., M.Kn.',
            'alasan' => 'meninggal', 'tanggal' => '2019-03-18', 'nomor_berita_acara' => 'BA-07/MPD-KB/III/2019', 'tahun_awal' => 1984, 'tahun_akhir' => 2018,
            'jumlah_buku' => 126, 'ada_protokol_25_tahun' => true, 'catatan' => 'Protokol berumur 25 tahun atau lebih disimpan di gudang G-01. '.self::LABEL]);
    }

    /** Contoh riwayat penggunaan AI (fitur AI di situs demo tetap mati). */
    private function logAi(): void
    {
        $budi = Subjek::query()->where('nama', 'Budi Santoso')->first();
        $pt = Berkas::query()->where('judul', 'Pendirian PT Sinar Kopi Priangan')->first();
        foreach ([
            ['ekstraksi', 'berhasil', 'KTP Budi Santoso dibaca; 14 kolom diisi, 1 perlu dicek (RT/RW).', 1450, 380, 0.00410, 12],
            ['pemeriksa', 'berhasil', 'Draf akta pendirian PT: 2 temuan (terbilang modal, nama penghadap kedua).', 6200, 640, 0.01230, 5],
            ['pemeriksa', 'ditolak', null, 0, 0, 0, 3],
        ] as [$fitur, $status, $ringkasan, $in, $out, $biaya, $hari]) {
            AiPermintaan::create(['user_id' => $this->u['staf']->id, 'fitur' => $fitur, 'berkas_id' => $pt?->id, 'subjek_ai_type' => $fitur === 'ekstraksi' ? Subjek::class : null,
                'subjek_ai_id' => $fitur === 'ekstraksi' ? $budi?->id : null, 'model' => $status === 'ditolak' ? null : 'gpt-4.1-mini', 'input_tokens' => $in, 'output_tokens' => $out,
                'biaya_usd' => $biaya, 'status' => $status, 'ringkasan' => $ringkasan, 'galat' => $status === 'ditolak' ? 'Berkas sangat rahasia: AI tidak dipakai.' : null,
                'created_at' => today()->subDays($hari)->setTime(10, 15), 'updated_at' => today()->subDays($hari)->setTime(10, 15)]);
        }
    }

    /** Dokumen pindaian contoh berupa PDF satu halaman bertanda DATA CONTOH. */
    private function dokumen($pemilik, string $jenis, string $judul, ?string $berlaku = null): void
    {
        $path = 'contoh/'.str($judul)->slug().'.pdf';
        Storage::disk('local')->put($path, $this->pdfContoh($judul));
        Dokumen::create(['dokumenable_type' => $pemilik::class, 'dokumenable_id' => $pemilik->id, 'jenis' => $jenis, 'judul' => $judul, 'path' => $path,
            'nama_asli' => basename($path), 'berlaku_sampai' => $berlaku, 'uploaded_by' => $this->u['staf']->id]);
    }

    private function pdfContoh(string $judul): string
    {
        $esc = fn (string $t) => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $t);
        $isi = "BT /F1 28 Tf 72 740 Td (DATA CONTOH) Tj ET\nBT /F1 16 Tf 72 700 Td (".$esc($judul).") Tj ET\n"
            ."BT /F1 11 Tf 72 676 Td (Dokumen fiktif untuk peragaan Ruci Notaria. Bukan dokumen sebenarnya.) Tj ET\n"
            ."0.8 0.6 0.2 RG 4 w 60 640 m 535 640 l S\n";
        $obj = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', '<< /Length '.strlen($isi)." >>\nstream\n".$isi.'endstream'];
        $pdf = "%PDF-1.4\n";
        $ofs = [];
        foreach ($obj as $i => $o) {
            $ofs[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$o."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($obj) + 1)."\n0000000000 65535 f \n";
        foreach ($ofs as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }

        return $pdf."trailer\n<< /Size ".(count($obj) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";
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
