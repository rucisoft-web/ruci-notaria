<?php

namespace Tests;

use App\Enums\Kedudukan;
use App\Enums\Role;
use App\Models\Akta;
use App\Models\Berkas;
use App\Models\Jabatan;
use App\Models\JenisLayanan;
use App\Models\Subjek;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function pengguna(Role $role = Role::Notaris, array $attributes = []): User
    {
        static $n = 0;
        $n++;

        return User::create($attributes + ['name' => "Pengguna {$n}", 'email' => "u{$n}-".uniqid().'@uji.test', 'password' => 'rahasia-uji-123', 'role' => $role, 'is_active' => true]);
    }

    protected function jabatan(array $attributes = []): Jabatan
    {
        return Jabatan::create($attributes + [
            'nama' => 'Notaris Uji', 'nama_lengkap' => 'NOTARIS UJI, Sarjana Hukum, Magister Kenotariatan', 'tempat_kedudukan' => 'Kota Bandung',
            'provinsi' => 'Jawa Barat', 'kode' => 'NU', 'tanggal_lahir' => '1980-01-01', 'is_active' => true,
        ]);
    }

    protected function orang(array $attributes = []): Subjek
    {
        static $n = 0;
        $n++;

        return Subjek::create($attributes + [
            'jenis' => 'orang', 'nama' => "Orang {$n}", 'nik' => '32730100000000'.str_pad((string) $n, 2, '0', STR_PAD_LEFT), 'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '1985-05-05', 'jenis_kelamin' => 'L', 'pekerjaan' => 'Wiraswasta', 'status_perkawinan' => 'kawin', 'alamat' => 'Jalan Uji',
            'kota' => 'Kota Bandung', 'provinsi' => 'Jawa Barat',
        ]);
    }

    protected function layanan(array $attributes = []): JenisLayanan
    {
        return JenisLayanan::create($attributes + ['kode' => 'UJI-'.uniqid(), 'nama' => 'Perjanjian', 'kategori' => 'akta', 'checklist' => ['KTP'], 'aturan_tenggat' => ['T-SAL']]);
    }

    protected function berkas(Jabatan $jabatan, array $attributes = []): Berkas
    {
        return Berkas::create($attributes + ['judul' => 'Berkas uji', 'jenis_layanan_id' => $this->layanan()->id, 'jabatan_id' => $jabatan->id, 'klien_id' => $this->orang()->id]);
    }

    /** Akta dengan dua penghadap dan dua saksi, siap diberi nomor. */
    protected function aktaSiap(Jabatan $jabatan, array $attributes = [], ?Berkas $berkas = null): Akta
    {
        $berkas ??= $this->berkas($jabatan);
        $akta = Akta::create($attributes + ['berkas_id' => $berkas->id, 'judul' => 'Perjanjian Uji', 'kota_tanda_tangan' => 'Kota Bandung', 'provinsi_tanda_tangan' => 'Jawa Barat']);
        foreach ([$this->orang(), $this->orang()] as $i => $s) {
            $akta->penghadap()->create(['subjek_id' => $s->id, 'kedudukan' => Kedudukan::DiriSendiri, 'urutan' => $i]);
        }
        foreach ([$this->orang(), $this->orang()] as $s) {
            $akta->saksi()->create(['subjek_id' => $s->id, 'jenis' => 'akta']);
        }

        return $akta;
    }
}
