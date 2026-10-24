<?php

/*
| Konfigurasi Ruci Notaria.
| Aturan tenggat di bawah adalah bawaan; angka hari dapat disesuaikan di sini bila peraturan berubah.
| Dasar hukum dicantumkan supaya mudah diverifikasi ulang.
*/

return [
    'mfa_required' => (bool) env('NOTARIA_MFA_REQUIRED', false),

    'uploads' => [
        'max_kb' => (int) env('NOTARIA_MAX_UPLOAD_KB', 20480),
    ],

    'admin' => [
        'email' => env('NOTARIA_ADMIN_EMAIL'),
        'password' => env('NOTARIA_ADMIN_PASSWORD'),
    ],

    'demo' => [
        'enabled' => (bool) env('NOTARIA_DEMO', false),
        'password' => env('NOTARIA_DEMO_PASSWORD', 'Demo#2026notaria'),
    ],

    /*
    | Tenggat yang dipicu oleh akta yang ditandatangani. Jenis layanan memilih kode yang berlaku.
    | basis: tanggal akta. hari_kerja: true = dihitung hari kerja (melewati Sabtu, Minggu, dan hari libur).
    */
    'tenggat_akta' => [
        'T-PT' => ['judul' => 'Ajukan pengesahan badan hukum PT di AHU', 'hari' => 60, 'hari_kerja' => false, 'dasar' => 'UU PT Pasal 10'],
        'T-AD' => ['judul' => 'Ajukan persetujuan/pemberitahuan perubahan AD/data PT di AHU', 'hari' => 30, 'hari_kerja' => false, 'dasar' => 'UU PT Pasal 21'],
        'T-FID' => ['judul' => 'Daftarkan jaminan fidusia di AHU', 'hari' => 30, 'hari_kerja' => false, 'dasar' => 'PP 21/2015'],
        'T-YYS' => ['judul' => 'Ajukan pengesahan/pemberitahuan yayasan di AHU', 'hari' => 30, 'hari_kerja' => false, 'dasar' => 'Ketentuan yayasan (verifikasi)'],
        'T-PKM' => ['judul' => 'Ajukan pengesahan perkumpulan di AHU', 'hari' => 30, 'hari_kerja' => false, 'dasar' => 'Ketentuan perkumpulan (verifikasi)'],
        'T-CV' => ['judul' => 'Daftarkan CV/Firma di SABU', 'hari' => 30, 'hari_kerja' => false, 'dasar' => 'Ketentuan pendaftaran CV (verifikasi)'],
        'T-PPAT' => ['judul' => 'Sampaikan akta PPAT dan warkah ke Kantor Pertanahan', 'hari' => 7, 'hari_kerja' => true, 'dasar' => 'PP 24/1997 Pasal 40'],
        'T-SKMHT' => ['judul' => 'Buat APHT sebelum SKMHT kedaluwarsa (tanah terdaftar)', 'hari' => 30, 'hari_kerja' => false, 'dasar' => 'UU HT Pasal 15'],
        'T-SAL' => ['judul' => 'Serahkan salinan akta kepada para pihak', 'hari' => 7, 'hari_kerja' => true, 'dasar' => 'Standar layanan kantor'],
    ],

    /*
    | Tenggat bulanan per notaris. hari = tanggal pada bulan berikutnya.
    */
    'tenggat_bulanan' => [
        'T-MPD' => ['judul' => 'Kirim salinan daftar akta & daftar lain bulan lalu ke MPD', 'hari' => 15, 'dasar' => 'UUJN Pasal 61'],
        'T-WST' => ['judul' => 'Kirim daftar wasiat/nihil bulan lalu ke Daftar Pusat Wasiat (AHU)', 'hari' => 5, 'dasar' => 'UUJN Pasal 16 ayat 1 huruf j'],
        'T-JLD' => ['judul' => 'Jilid minuta bulan lalu (maks. 50 akta per buku)', 'hari' => 10, 'dasar' => 'UUJN Pasal 16 ayat 1 huruf g'],
    ],

    // Biaya per 1 juta token (USD) untuk memperkirakan biaya AI. Sesuaikan dengan tarif model yang dipakai.
    'ai' => [
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-5-mini'),
        'harga_input_per_juta' => (float) env('OPENAI_PRICE_INPUT', 0.25),
        'harga_output_per_juta' => (float) env('OPENAI_PRICE_OUTPUT', 2.0),
        'timeout' => (int) env('OPENAI_TIMEOUT', 90),
    ],
];
