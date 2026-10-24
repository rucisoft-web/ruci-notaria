<?php

namespace Database\Seeders;

use App\Models\JenisLayanan;
use Illuminate\Database\Seeder;

/** Katalog layanan awal. Dapat diubah di menu Pengaturan › Jenis Layanan; seeder tidak menimpa ubahan kantor. */
class JenisLayananSeeder extends Seeder
{
    public const IDENTITAS = ['KTP para pihak', 'NPWP para pihak', 'Kartu Keluarga'];

    public function run(): void
    {
        $badan = ['Akta pendirian dan perubahan terakhir', 'SK pengesahan/persetujuan Kemenkum', 'NIB', 'Surat persetujuan RUPS/organ'];
        $daftar = [
            ['PT-DIRI', 'Pendirian perseroan terbatas', 'akta', 'umum', [...self::IDENTITAS, 'Bukti pemesanan nama PT', 'Data modal dan susunan saham', 'Susunan direksi dan dewan komisaris', 'Alamat kedudukan'], ['T-PT', 'T-SAL'], 10],
            ['PT-UBAH', 'Perubahan anggaran dasar/data PT', 'akta', 'umum', [...self::IDENTITAS, ...$badan, 'Notulen/keputusan RUPS'], ['T-AD', 'T-SAL'], 20],
            ['CV-DIRI', 'Pendirian CV/firma', 'akta', 'umum', [...self::IDENTITAS, 'Alamat kedudukan', 'Besarnya pemasukan sekutu'], ['T-CV', 'T-SAL'], 30],
            ['YYS-DIRI', 'Pendirian yayasan', 'akta', 'umum', [...self::IDENTITAS, 'Bukti pemesanan nama yayasan', 'Susunan pembina, pengurus, pengawas', 'Kekayaan awal'], ['T-YYS', 'T-SAL'], 40],
            ['PKM-DIRI', 'Pendirian perkumpulan', 'akta', 'umum', [...self::IDENTITAS, 'Bukti pemesanan nama perkumpulan', 'Susunan pengurus'], ['T-PKM', 'T-SAL'], 50],
            ['FIDUSIA', 'Akta jaminan fidusia', 'akta', 'umum', [...self::IDENTITAS, 'Perjanjian kredit', 'Bukti kepemilikan objek (BPKB/faktur/daftar persediaan)', 'Surat kuasa'], ['T-FID', 'T-SAL'], 60],
            ['SKMHT', 'Surat kuasa membebankan hak tanggungan', 'akta', 'umum', [...self::IDENTITAS, 'Sertipikat hak atas tanah', 'Perjanjian kredit'], ['T-SKMHT', 'T-SAL'], 70],
            ['PERJANJIAN', 'Perjanjian (sewa, kerja sama, PPJB, dan lainnya)', 'akta', 'umum', [...self::IDENTITAS, 'Draf/term sheet dari para pihak', 'Bukti kepemilikan objek'], ['T-SAL'], 80],
            ['KUASA', 'Akta kuasa', 'akta', 'umum', [...self::IDENTITAS, 'Bukti kepemilikan objek kuasa'], ['T-SAL'], 90],
            ['PERJ-KAWIN', 'Perjanjian perkawinan', 'akta', 'umum', [...self::IDENTITAS, 'Akta/buku nikah (bila sudah menikah)', 'Daftar harta bawaan'], ['T-SAL'], 100],
            ['UTANG', 'Pengakuan utang', 'akta', 'pengakuan_utang', [...self::IDENTITAS, 'Bukti utang/perjanjian dasar'], ['T-SAL'], 110],
            ['WASIAT', 'Wasiat (testamen) umum', 'akta', 'wasiat', ['KTP pewasiat', 'Kartu Keluarga', 'Daftar harta dan ahli waris/penerima'], [], 120],
            ['WARIS', 'Keterangan hak waris', 'akta', 'umum', ['Akta kematian pewaris', 'KTP dan KK ahli waris', 'Akta/buku nikah pewaris', 'Akta kelahiran ahli waris', 'Surat keterangan wasiat dari AHU'], ['T-SAL'], 130],
            ['PROTES', 'Protes wesel/cek', 'akta', 'protes', ['Surat berharga asli', 'Identitas pemegang'], [], 140],
            ['LEGALISASI', 'Legalisasi surat di bawah tangan', 'legalisasi', 'umum', ['KTP penanda tangan', 'Surat yang akan ditandatangani'], [], 200],
            ['WAARMERKING', 'Waarmerking (pembukuan surat)', 'waarmerking', 'umum', ['Surat yang sudah ditandatangani'], [], 210],
            ['FOTOKOPI', 'Pengesahan kecocokan fotokopi', 'fotokopi', 'umum', ['Dokumen asli', 'Fotokopi'], [], 220],
            ['PPAT-AJB', 'Akta jual beli (PPAT)', 'ppat', 'umum', [...self::IDENTITAS, 'Sertipikat asli', 'SPPT PBB 5 tahun terakhir', 'Bukti bayar PPh dan BPHTB', 'Persetujuan pasangan'], ['T-PPAT'], 300],
        ];

        foreach ($daftar as [$kode, $nama, $kategori, $kategoriAkta, $checklist, $tenggat, $urutan]) {
            JenisLayanan::query()->firstOrCreate(['kode' => $kode], [
                'nama' => $nama, 'kategori' => $kategori, 'kategori_akta' => $kategoriAkta, 'sifat_akta' => 'minuta',
                'checklist' => $checklist, 'aturan_tenggat' => $tenggat, 'urutan' => $urutan, 'is_active' => true,
            ]);
        }
    }
}
