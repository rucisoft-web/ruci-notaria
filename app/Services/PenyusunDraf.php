<?php

namespace App\Services;

use App\Enums\Kedudukan;
use App\Enums\StatusTemplate;
use App\Models\Akta;
use App\Models\AktaPenghadap;
use App\Models\AktaSaksi;
use App\Models\DrafAkta;
use App\Models\Pengaturan;
use App\Models\TemplateAkta;
use App\Support\Docx;
use App\Support\Terbilang;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\TemplateProcessor;

/**
 * Menyusun draf akta .docx dari template dan data akta.
 * Variabel yang tersedia didokumentasikan di VARIABEL dan ditampilkan di halaman template.
 */
class PenyusunDraf
{
    public const VARIABEL = [
        'judul' => 'Judul akta', 'judul_kapital' => 'Judul akta huruf kapital', 'nomor_akta' => 'Nomor akta (titik-titik bila belum bernomor)',
        'hari_tanggal' => 'hari Sabtu, tanggal dua puluh empat Oktober dua ribu dua puluh enam (24-10-2026)', 'tanggal' => '24-10-2026',
        'tanggal_terbilang' => 'dua puluh empat Oktober dua ribu dua puluh enam', 'hari' => 'Sabtu', 'jam' => 'Pukul 10.30 WIB (sepuluh lewat tiga puluh menit Waktu Indonesia Barat)',
        'notaris_nama' => 'Nama lengkap notaris dengan gelar', 'notaris_kedudukan' => 'Tempat kedudukan notaris', 'notaris_provinsi' => 'Wilayah jabatan',
        'notaris_sk' => 'Nomor dan tanggal SK pengangkatan', 'kota_tanda_tangan' => 'Kota tempat akta ditandatangani',
        'nilai' => 'Nilai ekonomis dengan angka dan huruf', 'nilai_terbilang' => 'Nilai ekonomis dengan huruf', 'berkas_nomor' => 'Nomor berkas',
        'klien_nama' => 'Nama klien', 'jumlah_penghadap' => 'Jumlah penghadap dengan huruf',
        'penutup_pembacaan' => 'Kalimat pembacaan (dibacakan / tidak dibacakan, Pasal 16 ayat 7)', 'renvoi' => 'Uraian ada tidaknya perubahan',
        'komparisi_1, komparisi_2, …' => 'Komparisi penghadap ke-n', 'penghadap_1_nama, penghadap_1_nik, …' => 'Data penghadap ke-n',
        'saksi_1_uraian, saksi_1_nama, …' => 'Data saksi akta ke-n',
        '${penghadap} … ${/penghadap}' => 'Blok berulang per penghadap: p_nomor, p_komparisi, p_nama, p_nik, p_tempat_lahir, p_tanggal_lahir, p_pekerjaan, p_alamat, p_kedudukan',
        '${saksi} … ${/saksi}' => 'Blok berulang per saksi akta: s_nomor, s_uraian, s_nama',
    ];

    public static function sapaan(array $d): string
    {
        if (($d['jenis'] ?? 'orang') === 'badan') {
            return '';
        }

        return match (true) {
            ($d['jenis_kelamin'] ?? null) === 'L' => 'Tuan',
            ($d['jenis_kelamin'] ?? null) === 'P' && ($d['status_perkawinan'] ?? null) === 'belum_kawin' => 'Nona',
            ($d['jenis_kelamin'] ?? null) === 'P' => 'Nyonya',
            default => 'Tuan/Nyonya',
        };
    }

    /** Uraian identitas sesuai Pasal 38 ayat 3 UUJN. */
    public static function identitas(array $d): string
    {
        $alamat = collect([
            $d['alamat'] ?? null,
            ($d['rt_rw'] ?? null) ? 'Rukun Tetangga/Rukun Warga '.$d['rt_rw'] : null,
            ($d['kelurahan'] ?? null) ? 'Kelurahan/Desa '.$d['kelurahan'] : null,
            ($d['kecamatan'] ?? null) ? 'Kecamatan '.$d['kecamatan'] : null,
            $d['kota'] ?? null,
        ])->filter()->implode(', ');
        $lahir = ($d['tanggal_lahir'] ?? null)
            ? ', lahir di '.($d['tempat_lahir'] ?? '…').', pada tanggal '.Terbilang::tanggal($d['tanggal_lahir']).' ('.Terbilang::tanggalAngka($d['tanggal_lahir']).')'
            : '';
        $wn = mb_strtolower($d['kewarganegaraan'] ?? 'Indonesia') === 'indonesia' ? 'Warga Negara Indonesia' : 'Warga Negara '.$d['kewarganegaraan'];
        $identitas = ($d['jenis_identitas'] ?? 'ktp') === 'paspor'
            ? 'pemegang Paspor Nomor '.($d['nik'] ?? '…')
            : 'pemegang Kartu Tanda Penduduk dengan Nomor Induk Kependudukan '.($d['nik'] ?? '…');

        return trim(static::sapaan($d).' '.mb_strtoupper($d['nama'] ?? '')).$lahir.', '.$wn
            .(($d['pekerjaan'] ?? null) ? ', '.$d['pekerjaan'] : '')
            .($alamat ? ', bertempat tinggal di '.$alamat : '')
            .', '.$identitas.';';
    }

    public static function komparisi(AktaPenghadap $p): string
    {
        $d = $p->data();
        $badan = $p->mewakili?->nama;
        $dasar = $p->keterangan_kedudukan ? ' '.$p->keterangan_kedudukan : '';

        $tindak = match ($p->kedudukan) {
            Kedudukan::DiriSendiri => '-menurut keterangannya dalam hal ini bertindak untuk diri sendiri.',
            Kedudukan::Kuasa => '-menurut keterangannya dalam hal ini bertindak selaku kuasa dari dan oleh karena itu untuk dan atas nama '.($badan ?? '…').$dasar.'.',
            Kedudukan::MewakiliBadan => '-menurut keterangannya dalam hal ini bertindak'.($dasar ?: ' dalam jabatannya').' dan oleh karena itu untuk dan atas nama '.($badan ?? '…').'.',
        };

        return static::identitas($d).' '.$tindak;
    }

    public static function uraianSaksi(AktaSaksi $s): string
    {
        return static::identitas($s->data());
    }

    /** @return array<string, string> */
    public function variabel(Akta $akta): array
    {
        $akta->loadMissing('penghadap.subjek', 'penghadap.mewakili', 'saksi.subjek', 'jabatan', 'berkas.klien');
        $j = $akta->jabatan;
        $zona = Pengaturan::get('kantor.zona_waktu', 'WIB');
        $tanggal = $akta->tanggal ?? ($akta->jadwal_at ? Carbon::parse($akta->jadwal_at) : null);
        $jam = $akta->jam ? substr((string) $akta->jam, 0, 5) : ($akta->jadwal_at ? Carbon::parse($akta->jadwal_at)->format('H:i') : null);
        $titik = '…………';

        $v = [
            'judul' => $akta->judul,
            'judul_kapital' => mb_strtoupper($akta->judul),
            'nomor_akta' => $akta->isBernomor() ? (string) $akta->nomor_bulanan : $titik,
            'hari_tanggal' => $tanggal ? Terbilang::hariTanggal($tanggal) : 'hari '.$titik.', tanggal '.$titik,
            'tanggal' => $tanggal ? Terbilang::tanggalAngka($tanggal) : $titik,
            'tanggal_terbilang' => $tanggal ? Terbilang::tanggal($tanggal) : $titik,
            'hari' => $tanggal ? Terbilang::hari($tanggal) : $titik,
            'jam' => $jam ? Terbilang::jam($jam, $zona) : 'Pukul '.$titik.' '.$zona,
            'notaris_nama' => $j?->nama_lengkap ?? '',
            'notaris_kedudukan' => $j?->tempat_kedudukan ?? '',
            'notaris_provinsi' => $j?->provinsi ?? '',
            'notaris_sk' => trim(($j?->nomor_sk ?? '').($j?->tanggal_sk ? ' tanggal '.Terbilang::tanggal($j->tanggal_sk).' ('.Terbilang::tanggalAngka($j->tanggal_sk).')' : '')),
            'kota_tanda_tangan' => $akta->kota_tanda_tangan ?? $j?->tempat_kedudukan ?? '',
            'nilai' => $akta->nilai_ekonomis !== null ? Terbilang::rupiah($akta->nilai_ekonomis) : '',
            'nilai_terbilang' => $akta->nilai_ekonomis !== null ? Terbilang::rupiah($akta->nilai_ekonomis, false) : '',
            'berkas_nomor' => $akta->berkas->nomor,
            'klien_nama' => $akta->berkas->klien?->nama ?? '',
            'jumlah_penghadap' => Terbilang::angka($akta->penghadap->count()),
            'penutup_pembacaan' => $akta->dibacakan
                ? 'Segera setelah akta ini dibacakan oleh saya, Notaris, kepada para penghadap dan para saksi, maka akta ini ditandatangani oleh para penghadap, para saksi, dan saya, Notaris.'
                : 'Para penghadap menyatakan telah membaca sendiri, mengetahui, dan memahami isi akta ini sehingga akta ini tidak dibacakan; setiap halaman minuta akta ini telah diparaf, dan akta ini ditandatangani oleh para penghadap, para saksi, dan saya, Notaris.',
            'renvoi' => $this->uraianRenvoi($akta),
        ];

        foreach ($akta->penghadap->values() as $i => $p) {
            $n = $i + 1;
            $d = $p->data();
            $v["komparisi_{$n}"] = static::komparisi($p);
            $v["penghadap_{$n}_nama"] = mb_strtoupper($d['nama'] ?? '');
            $v["penghadap_{$n}_nik"] = (string) ($d['nik'] ?? '');
            $v["penghadap_{$n}_alamat"] = (string) ($d['alamat'] ?? '');
        }
        foreach ($akta->saksi->where('jenis', 'akta')->values() as $i => $s) {
            $n = $i + 1;
            $v["saksi_{$n}_uraian"] = static::uraianSaksi($s);
            $v["saksi_{$n}_nama"] = mb_strtoupper($s->data()['nama'] ?? '');
        }

        return $v;
    }

    public function uraianRenvoi(Akta $akta): string
    {
        if ($akta->jumlahRenvoi() === 0) {
            return 'Dilangsungkan dengan tanpa perubahan.';
        }
        $bagian = collect(['tambahan' => $akta->renvoi_tambah, 'coretan' => $akta->renvoi_coret, 'penggantian' => $akta->renvoi_ganti])
            ->filter()->map(fn ($n, $k) => Terbilang::angka($n).' ('.$n.') '.$k)->implode(', ');

        return 'Dilangsungkan dengan '.$bagian.'.';
    }

    public function buat(Akta $akta, TemplateAkta $template): DrafAkta
    {
        if ($template->status !== StatusTemplate::Disetujui) {
            throw ValidationException::withMessages(['template' => 'Template belum disetujui notaris.']);
        }

        $proc = new TemplateProcessor(Storage::disk('local')->path($template->path));
        $esc = fn (?string $s) => htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $akta->loadMissing('penghadap.subjek', 'penghadap.mewakili', 'saksi.subjek');
        $ada = array_flip($proc->getVariables());

        if (isset($ada['penghadap'])) {
            $proc->cloneBlock('penghadap', 0, true, false, $akta->penghadap->values()->map(fn ($p, $i) => array_map($esc, [
                'p_nomor' => (string) ($i + 1), 'p_komparisi' => static::komparisi($p), 'p_nama' => mb_strtoupper($p->data()['nama'] ?? ''),
                'p_nik' => (string) ($p->data()['nik'] ?? ''), 'p_tempat_lahir' => (string) ($p->data()['tempat_lahir'] ?? ''),
                'p_tanggal_lahir' => ($p->data()['tanggal_lahir'] ?? null) ? Terbilang::tanggalAngka($p->data()['tanggal_lahir']) : '',
                'p_pekerjaan' => (string) ($p->data()['pekerjaan'] ?? ''), 'p_alamat' => (string) ($p->data()['alamat'] ?? ''),
                'p_kedudukan' => $p->kedudukan->getLabel(),
            ]))->all() ?: [['p_nomor' => '', 'p_komparisi' => '…', 'p_nama' => '', 'p_nik' => '', 'p_tempat_lahir' => '', 'p_tanggal_lahir' => '', 'p_pekerjaan' => '', 'p_alamat' => '', 'p_kedudukan' => '']]);
        }
        if (isset($ada['saksi'])) {
            $saksi = $akta->saksi->where('jenis', 'akta')->values();
            $proc->cloneBlock('saksi', 0, true, false, $saksi->map(fn ($s, $i) => array_map($esc, [
                's_nomor' => (string) ($i + 1), 's_uraian' => static::uraianSaksi($s), 's_nama' => mb_strtoupper($s->data()['nama'] ?? ''),
            ]))->all() ?: [['s_nomor' => '', 's_uraian' => '…', 's_nama' => '']]);
        }

        $proc->setValues(array_map($esc, $this->variabel($akta)));

        $versi = (int) $akta->draf()->max('versi') + 1;
        $path = "draf/akta-{$akta->id}/v{$versi}.docx";
        Storage::disk('local')->makeDirectory("draf/akta-{$akta->id}");
        $proc->saveAs(Storage::disk('local')->path($path));
        Docx::normalisasiTab(Storage::disk('local')->path($path));

        return $akta->draf()->create([
            'template_akta_id' => $template->id, 'versi' => $versi, 'path' => $path, 'sumber' => 'template', 'created_by' => auth()->id(),
        ]);
    }
}
