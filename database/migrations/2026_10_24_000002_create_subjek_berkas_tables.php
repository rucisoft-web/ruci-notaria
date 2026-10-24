<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Klien/penghadap (subjek), relasi keluarga, dokumen, jenis layanan, berkas, dan tugas. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjek', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 10)->default('orang')->comment('orang | badan');
            $table->string('nama');
            // Nomor identitas disimpan terenkripsi; pencarian memakai hash (indeks buta).
            $table->text('nik')->nullable();
            $table->string('nik_hash', 64)->nullable()->index();
            $table->string('jenis_identitas', 20)->default('ktp')->comment('ktp | paspor | lainnya');
            $table->date('identitas_berlaku_sampai')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('kewarganegaraan', 60)->default('Indonesia');
            $table->string('pekerjaan')->nullable();
            $table->string('status_perkawinan', 20)->nullable()->comment('belum_kawin | kawin | cerai_hidup | cerai_mati');
            $table->text('alamat')->nullable();
            $table->string('rt_rw', 20)->nullable();
            $table->string('kelurahan')->nullable();
            $table->string('kecamatan')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();
            $table->text('npwp')->nullable();
            $table->string('telepon', 30)->nullable();
            $table->string('email')->nullable();
            // Badan
            $table->string('bentuk_badan', 30)->nullable()->comment('pt | yayasan | perkumpulan | koperasi | cv | firma | instansi | lainnya');
            $table->string('nomor_sk_badan')->nullable();
            $table->string('nib', 30)->nullable();
            $table->boolean('saksi_kantor')->default(false)->comment('Staf yang biasa menjadi saksi akta');
            $table->boolean('persetujuan_ai')->default(false)->comment('Persetujuan pemrosesan data oleh penyedia AI');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['jenis', 'nama']);
        });

        Schema::table('jabatan', function (Blueprint $table) {
            $table->foreignId('subjek_id')->nullable()->after('user_id')->constrained('subjek')->nullOnDelete()
                ->comment('Data diri notaris untuk pemeriksaan hubungan keluarga (Pasal 52 UUJN)');
        });

        Schema::create('relasi_subjek', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subjek_id')->constrained('subjek')->cascadeOnDelete();
            $table->foreignId('terkait_id')->constrained('subjek')->cascadeOnDelete();
            $table->string('hubungan', 30);
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['subjek_id', 'terkait_id', 'hubungan']);
        });

        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();
            $table->morphs('dokumenable');
            $table->string('jenis', 40);
            $table->string('judul');
            $table->string('path');
            $table->string('nama_asli')->nullable();
            $table->date('berlaku_sampai')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('jenis_layanan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->unique();
            $table->string('nama');
            $table->string('kategori', 20)->default('akta')->comment('akta | legalisasi | waarmerking | fotokopi | ppat | lainnya');
            $table->string('kategori_akta', 30)->default('umum')->comment('umum | wasiat | wasiat_bawah_tangan | protes | pengakuan_utang');
            $table->string('sifat_akta', 12)->default('minuta');
            $table->json('checklist')->nullable();
            $table->json('aturan_tenggat')->nullable();
            $table->decimal('honorarium_bawaan', 15, 2)->default(0);
            $table->unsignedSmallInteger('urutan')->default(100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('berkas', function (Blueprint $table) {
            $table->id();
            $table->string('nomor', 30)->unique();
            $table->string('judul');
            $table->foreignId('jenis_layanan_id')->constrained('jenis_layanan')->restrictOnDelete();
            $table->foreignId('jabatan_id')->constrained('jabatan')->restrictOnDelete();
            $table->foreignId('klien_id')->nullable()->constrained('subjek')->nullOnDelete();
            $table->foreignId('pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('baru');
            $table->boolean('sangat_rahasia')->default(false);
            $table->boolean('cuma_cuma')->default(false)->comment('Jasa hukum cuma-cuma, Pasal 37 UUJN');
            $table->decimal('nilai_transaksi', 18, 2)->nullable();
            $table->date('tanggal_masuk');
            $table->date('tanggal_selesai')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'updated_at']);
        });

        Schema::create('berkas_anggota', function (Blueprint $table) {
            $table->foreignId('berkas_id')->constrained('berkas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['berkas_id', 'user_id']);
        });

        Schema::create('berkas_pihak', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berkas_id')->constrained('berkas')->cascadeOnDelete();
            $table->foreignId('subjek_id')->constrained('subjek')->restrictOnDelete();
            $table->string('peran', 60);
            $table->timestamps();
            $table->unique(['berkas_id', 'subjek_id', 'peran']);
        });

        Schema::create('berkas_checklist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berkas_id')->constrained('berkas')->cascadeOnDelete();
            $table->string('nama');
            $table->string('status', 20)->default('diminta');
            $table->foreignId('dokumen_id')->nullable()->constrained('dokumen')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('tugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berkas_id')->constrained('berkas')->cascadeOnDelete();
            $table->string('judul');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('jatuh_tempo')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('tanda_terima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berkas_id')->constrained('berkas')->cascadeOnDelete();
            $table->string('nomor', 30)->unique();
            $table->string('arah', 10)->comment('masuk | keluar');
            $table->date('tanggal');
            $table->string('pihak');
            $table->text('daftar_dokumen');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['tanda_terima', 'tugas', 'berkas_checklist', 'berkas_pihak', 'berkas_anggota', 'berkas', 'jenis_layanan', 'dokumen', 'relasi_subjek'] as $t) {
            Schema::dropIfExists($t);
        }
        Schema::table('jabatan', fn (Blueprint $table) => $table->dropConstrainedForeignId('subjek_id'));
        Schema::dropIfExists('subjek');
    }
};
