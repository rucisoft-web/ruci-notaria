<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kantor, jabatan notaris, pengguna, pengaturan, hari libur, dan jejak audit.
 * Satu instalasi = satu kantor; satu kantor dapat menampung lebih dari satu notaris (jabatan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('staf')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('phone', 30)->nullable()->after('email');
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
        });

        Schema::create('pengaturan', function (Blueprint $table) {
            $table->string('key', 80)->primary();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('hari_libur', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->string('nama');
            $table->timestamps();
        });

        // Subjek (orang/badan) dibuat di migrasi berikutnya; jabatan merujuknya untuk pemeriksaan Pasal 52.
        Schema::create('jabatan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('nama_lengkap')->comment('Nama dengan gelar, sebagaimana ditulis di awal akta');
            $table->string('jenis', 20)->default('notaris')->comment('notaris | pengganti');
            $table->string('nomor_sk')->nullable();
            $table->date('tanggal_sk')->nullable();
            $table->date('tanggal_sumpah')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('tempat_kedudukan')->comment('Kabupaten/kota');
            $table->string('provinsi')->comment('Wilayah jabatan');
            $table->text('alamat_kantor')->nullable();
            $table->string('kode', 10)->default('N')->comment('Dipakai pada format nomor legalisasi/waarmerking');
            $table->boolean('is_ppat')->default(false);
            $table->string('daerah_kerja_ppat')->nullable();
            $table->date('cuti_mulai')->nullable();
            $table->date('cuti_selesai')->nullable();
            $table->foreignId('pengganti_id')->nullable()->constrained('jabatan')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 30);
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('label')->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('hash', 64)->nullable()->comment('Rantai hash: sha256(hash sebelumnya + isi baris)');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('created_at');
        });

        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->unique(['code', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequences');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('jabatan');
        Schema::dropIfExists('hari_libur');
        Schema::dropIfExists('pengaturan');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'phone', 'app_authentication_secret', 'app_authentication_recovery_codes']);
        });
    }
};
