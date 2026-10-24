<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Tenggat hukum, keuangan (tagihan, pembayaran, dana titipan), dan log AI. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenggat', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20);
            $table->string('judul');
            $table->date('jatuh_tempo');
            $table->foreignId('jabatan_id')->nullable()->constrained('jabatan')->cascadeOnDelete();
            $table->foreignId('berkas_id')->nullable()->constrained('berkas')->cascadeOnDelete();
            $table->foreignId('akta_id')->nullable()->constrained('akta')->cascadeOnDelete();
            $table->string('periode', 7)->nullable()->comment('YYYY-MM untuk tenggat bulanan');
            $table->string('kunci', 120)->unique()->comment('Mencegah tenggat ganda untuk pemicu yang sama');
            $table->timestamp('selesai_at')->nullable();
            $table->foreignId('selesai_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->index(['selesai_at', 'jatuh_tempo']);
        });

        Schema::create('tagihan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berkas_id')->constrained('berkas')->restrictOnDelete();
            $table->string('nomor', 30)->unique();
            $table->date('tanggal');
            $table->date('jatuh_tempo')->nullable();
            $table->string('status', 12)->default('draf')->comment('draf | terbit | lunas | batal');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tagihan_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->cascadeOnDelete();
            $table->string('jenis', 12)->comment('honorarium | titipan | lain');
            $table->string('uraian');
            $table->decimal('jumlah', 15, 2);
            $table->timestamps();
        });

        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tagihan_id')->constrained('tagihan')->restrictOnDelete();
            $table->string('nomor_kuitansi', 30)->unique();
            $table->date('tanggal');
            $table->decimal('jumlah', 15, 2);
            $table->string('metode', 20)->default('transfer');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Buku dana titipan hanya bertambah; koreksi dengan mutasi balik.
        Schema::create('dana_titipan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('berkas_id')->constrained('berkas')->restrictOnDelete();
            $table->date('tanggal');
            $table->string('arah', 6)->comment('masuk | keluar');
            $table->decimal('jumlah', 15, 2);
            $table->string('uraian');
            $table->string('bukti')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('ai_permintaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('fitur', 30);
            $table->foreignId('berkas_id')->nullable()->constrained('berkas')->nullOnDelete();
            $table->nullableMorphs('subjek_ai');
            $table->string('model', 80)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->decimal('biaya_usd', 10, 5)->default(0);
            $table->string('status', 12)->comment('berhasil | gagal | ditolak');
            $table->text('ringkasan')->nullable()->comment('Ringkasan masukan yang sudah disamarkan');
            $table->text('galat')->nullable();
            $table->string('keputusan', 12)->nullable()->comment('diterima | ditolak');
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        foreach (['ai_permintaan', 'dana_titipan', 'pembayaran', 'tagihan_item', 'tagihan', 'tenggat'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
