<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Template, draf, akta, buku daftar, surat di bawah tangan, salinan, dan protokol. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_akta', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->foreignId('jenis_layanan_id')->nullable()->constrained('jenis_layanan')->nullOnDelete();
            $table->unsignedSmallInteger('versi')->default(1);
            $table->string('status', 12)->default('draf')->comment('draf | disetujui | usang');
            $table->string('path');
            $table->text('catatan')->nullable();
            $table->foreignId('disetujui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disetujui_at')->nullable();
            $table->timestamps();
        });

        Schema::create('buku_fisik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jabatan_id')->constrained('jabatan')->restrictOnDelete();
            $table->string('jenis_daftar', 20);
            $table->string('nomor_buku', 30);
            $table->unsignedSmallInteger('jumlah_halaman');
            $table->date('tanggal_paraf_mpd')->nullable();
            $table->date('mulai_dipakai')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('lokasi_arsip', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('jilid_minuta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jabatan_id')->constrained('jabatan')->restrictOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan');
            $table->unsignedTinyInteger('nomor_jilid');
            $table->unsignedInteger('nomor_awal');
            $table->unsignedInteger('nomor_akhir');
            $table->unsignedSmallInteger('jumlah_minuta');
            $table->foreignId('lokasi_arsip_id')->nullable()->constrained('lokasi_arsip')->nullOnDelete();
            $table->timestamp('dijilid_at')->nullable();
            $table->timestamps();
            $table->unique(['jabatan_id', 'tahun', 'bulan', 'nomor_jilid']);
        });

        Schema::create('akta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berkas_id')->constrained('berkas')->restrictOnDelete();
            $table->foreignId('jabatan_id')->constrained('jabatan')->restrictOnDelete();
            $table->foreignId('jenis_layanan_id')->nullable()->constrained('jenis_layanan')->nullOnDelete();
            $table->string('judul');
            $table->string('sifat', 12)->default('minuta')->comment('minuta | originali');
            $table->string('kategori', 30)->default('umum');
            $table->string('status', 20)->default('draf')->comment('draf | dijadwalkan | ditandatangani | batal');
            // Jadwal
            $table->dateTime('jadwal_at')->nullable();
            $table->string('tempat')->nullable();
            $table->string('kota_tanda_tangan')->nullable();
            $table->string('provinsi_tanda_tangan')->nullable();
            // Penomoran (diisi saat penandatanganan dicatat, tidak boleh berubah)
            $table->unsignedSmallInteger('tahun')->nullable();
            $table->unsignedTinyInteger('bulan')->nullable();
            $table->unsignedInteger('nomor_bulanan')->nullable();
            $table->unsignedInteger('nomor_urut')->nullable();
            $table->date('tanggal')->nullable();
            $table->time('jam')->nullable();
            $table->foreignId('dinomori_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dinomori_at')->nullable();
            // Hasil penandatanganan
            $table->boolean('dibacakan')->default(true);
            $table->unsignedSmallInteger('renvoi_tambah')->default(0);
            $table->unsignedSmallInteger('renvoi_coret')->default(0);
            $table->unsignedSmallInteger('renvoi_ganti')->default(0);
            $table->text('catatan_penandatanganan')->nullable();
            $table->decimal('nilai_ekonomis', 18, 2)->nullable();
            $table->text('alasan_batal')->nullable();
            // Buku daftar fisik
            $table->foreignId('buku_fisik_id')->nullable()->constrained('buku_fisik')->nullOnDelete();
            $table->unsignedSmallInteger('halaman_buku')->nullable();
            // Minuta & protokol
            $table->boolean('minuta_lampiran_lengkap')->default(false);
            $table->boolean('minuta_sidik_jari_lengkap')->default(false);
            $table->string('minuta_scan_path')->nullable();
            $table->foreignId('jilid_minuta_id')->nullable()->constrained('jilid_minuta')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['jabatan_id', 'tahun', 'bulan', 'nomor_bulanan'], 'akta_nomor_bulanan_unik');
            $table->unique(['jabatan_id', 'tahun', 'nomor_urut'], 'akta_nomor_urut_unik');
            $table->index(['status', 'jadwal_at']);
            $table->index(['jabatan_id', 'tanggal']);
        });

        Schema::create('akta_penghadap', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akta_id')->constrained('akta')->cascadeOnDelete();
            $table->foreignId('subjek_id')->constrained('subjek')->restrictOnDelete();
            $table->string('kedudukan', 30)->default('diri_sendiri')->comment('diri_sendiri | kuasa | mewakili_badan');
            $table->foreignId('mewakili_id')->nullable()->constrained('subjek')->nullOnDelete();
            $table->string('keterangan_kedudukan')->nullable()->comment('Misal: selaku Direktur Utama berdasarkan akta ...');
            $table->string('tanda_tangan', 20)->default('tanda_tangan')->comment('tanda_tangan | cap_jempol | tidak');
            $table->string('alasan_tidak_tanda_tangan')->nullable();
            $table->boolean('sidik_jari')->default(false);
            $table->text('potret')->nullable()->comment('Potret data penghadap terenkripsi saat penomoran');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('akta_saksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akta_id')->constrained('akta')->cascadeOnDelete();
            $table->foreignId('subjek_id')->constrained('subjek')->restrictOnDelete();
            $table->string('jenis', 12)->default('akta')->comment('akta | pengenal');
            $table->text('potret')->nullable();
            $table->timestamps();
            $table->unique(['akta_id', 'subjek_id', 'jenis']);
        });

        Schema::create('draf_akta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akta_id')->constrained('akta')->cascadeOnDelete();
            $table->foreignId('template_akta_id')->nullable()->constrained('template_akta')->nullOnDelete();
            $table->unsignedSmallInteger('versi');
            $table->string('path');
            $table->string('sumber', 12)->default('template')->comment('template | unggahan');
            $table->boolean('final')->default(false);
            $table->json('temuan')->nullable()->comment('Hasil pemeriksa deterministik');
            $table->json('temuan_ai')->nullable();
            $table->timestamp('diperiksa_at')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['akta_id', 'versi']);
        });

        Schema::create('berita_acara_pembetulan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akta_id')->constrained('akta')->restrictOnDelete();
            $table->date('tanggal');
            $table->text('kesalahan');
            $table->text('pembetulan');
            $table->text('disampaikan_kepada')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('surat_bawah_tangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jabatan_id')->constrained('jabatan')->restrictOnDelete();
            $table->foreignId('berkas_id')->nullable()->constrained('berkas')->nullOnDelete();
            $table->string('jenis', 12)->comment('legalisasi | waarmerking | fotokopi');
            $table->unsignedSmallInteger('tahun');
            $table->unsignedInteger('nomor_urut');
            $table->string('nomor', 40);
            $table->date('tanggal');
            $table->date('tanggal_surat')->nullable()->comment('Tanggal surat yang dibukukan (waarmerking)');
            $table->string('judul');
            $table->text('para_pihak');
            $table->string('pemohon')->nullable();
            $table->unsignedSmallInteger('jumlah_lembar')->default(1);
            $table->string('scan_path')->nullable();
            $table->foreignId('buku_fisik_id')->nullable()->constrained('buku_fisik')->nullOnDelete();
            $table->unsignedSmallInteger('halaman_buku')->nullable();
            $table->boolean('batal')->default(false);
            $table->text('alasan_batal')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['jabatan_id', 'jenis', 'tahun', 'nomor_urut'], 'sbt_nomor_unik');
        });

        Schema::create('salinan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akta_id')->constrained('akta')->restrictOnDelete();
            $table->string('jenis', 10)->default('salinan')->comment('salinan | kutipan | grosse');
            $table->unsignedTinyInteger('grosse_ke')->nullable();
            $table->date('tanggal');
            $table->string('penerima');
            $table->string('dasar_hak', 30);
            $table->text('keterangan')->nullable();
            $table->boolean('ditolak')->default(false);
            $table->text('alasan_tolak')->nullable();
            $table->boolean('bermeterai')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('peminjaman_minuta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akta_id')->constrained('akta')->restrictOnDelete();
            $table->foreignId('peminjam_id')->constrained('users')->restrictOnDelete();
            $table->string('alasan');
            $table->dateTime('dipinjam_at');
            $table->date('jatuh_tempo');
            $table->dateTime('dikembalikan_at')->nullable();
            $table->timestamps();
        });

        Schema::create('permintaan_penegak_hukum', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akta_id')->nullable()->constrained('akta')->nullOnDelete();
            $table->string('jenis', 20)->comment('pemanggilan | fotokopi_minuta | lainnya');
            $table->string('instansi');
            $table->string('nomor_surat');
            $table->date('tanggal_surat');
            $table->string('nomor_persetujuan_mkn')->nullable();
            $table->date('tanggal_persetujuan_mkn')->nullable();
            $table->date('tanggal_pelaksanaan')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('serah_terima_protokol', function (Blueprint $table) {
            $table->id();
            $table->string('arah', 10)->comment('masuk | keluar');
            $table->string('notaris_asal');
            $table->string('notaris_penerima');
            $table->string('alasan');
            $table->date('tanggal');
            $table->string('nomor_berita_acara')->nullable();
            $table->unsignedSmallInteger('tahun_awal')->nullable();
            $table->unsignedSmallInteger('tahun_akhir')->nullable();
            $table->unsignedInteger('jumlah_buku')->default(0);
            $table->boolean('ada_protokol_25_tahun')->default(false)->comment('Wajib diserahkan ke MPD (Pasal 63 ayat 5)');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['serah_terima_protokol', 'permintaan_penegak_hukum', 'peminjaman_minuta', 'salinan', 'surat_bawah_tangan', 'berita_acara_pembetulan',
            'draf_akta', 'akta_saksi', 'akta_penghadap', 'akta', 'jilid_minuta', 'lokasi_arsip', 'buku_fisik', 'template_akta'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
