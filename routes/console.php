<?php

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Jabatan;
use App\Models\User;
use App\Services\KalkulatorTenggat;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

/*
| Perintah operasional Ruci Notaria.
| Cron cPanel cukup satu baris: * * * * * php /home/AKUN/ruci-notaria/artisan schedule:run
*/

Artisan::command('notaria:create-admin {email} {name} {--role=notaris} {--password=}', function (string $email, string $name) {
    $role = Role::tryFrom((string) $this->option('role'));
    if (! $role) {
        $this->error('Peran tidak dikenal. Pilih: '.implode(', ', array_column(Role::cases(), 'value')));

        return 1;
    }
    $password = $this->option('password') ?: $this->secret('Kata sandi (min. 10 karakter)');
    if (strlen((string) $password) < 10) {
        $this->error('Kata sandi minimal 10 karakter.');

        return 1;
    }

    $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => $password, 'role' => $role, 'is_active' => true]);
    $this->info("Pengguna {$user->email} ({$role->getLabel()}) siap. Masuk lewat /app.");
    if ($role === Role::Notaris && ! Jabatan::query()->exists()) {
        $this->line('Langkah berikut: isi data jabatan notaris di menu Pengaturan › Jabatan Notaris.');
    }
})->purpose('Buat atau perbarui pengguna (bawaan: notaris)');

Artisan::command('notaria:demo', function () {
    $this->call('db:seed', ['--class' => 'Database\\Seeders\\DemoSeeder', '--force' => true]);
})->purpose('Muat data contoh tiga kantor notaris fiktif');

Artisan::command('notaria:demo-reset', function () {
    if (! config('notaria.demo.enabled')) {
        $this->error('Hanya untuk situs demo (NOTARIA_DEMO=true). Perintah ini menghapus seluruh isi database.');

        return 1;
    }
    $disk = Storage::disk('local');
    foreach ($disk->directories() as $dir) {
        $disk->deleteDirectory($dir);
    }
    foreach ($disk->files() as $file) {
        if ($file !== '.gitignore') {
            $disk->delete($file);
        }
    }
    $this->call('migrate:fresh', ['--force' => true, '--seed' => true]);
    $this->info('Situs demo dikembalikan ke data contoh awal.');

    return 0;
})->purpose('Kosongkan database dan berkas lalu muat ulang data contoh (khusus situs demo)');

Artisan::command('notaria:tenggat-bulanan {--periode= : YYYY-MM, bawaan bulan lalu}', function (KalkulatorTenggat $kalkulator) {
    $periode = $this->option('periode') ? now()->createFromFormat('Y-m-d', $this->option('periode').'-01') : now()->subMonthNoOverflow();
    $n = $kalkulator->buatBulanan($periode);
    $this->info("{$n} tenggat bulanan dibuat untuk {$periode->format('m/Y')}.");
})->purpose('Buat tenggat laporan MPD, daftar wasiat, dan penjilidan untuk bulan lalu');

Artisan::command('notaria:periksa-audit', function () {
    $putus = AuditLog::verifyChain();
    if ($putus === null) {
        $this->info('Rantai jejak audit utuh ('.AuditLog::count().' baris).');

        return 0;
    }
    $this->error("Rantai jejak audit putus pada baris #{$putus}. Ada data audit yang diubah atau dihapus langsung di database.");

    return 1;
})->purpose('Verifikasi rantai hash jejak audit');

Artisan::command('notaria:doctor', function () {
    $ok = true;
    $cek = function (string $judul, bool $lulus, string $saran = '') use (&$ok) {
        $this->line(($lulus ? '<info>  OK </info> ' : '<error> GAGAL </error> ').$judul.(! $lulus && $saran ? " — {$saran}" : ''));
        $ok = $ok && $lulus;
    };

    $cek('PHP 8.3 atau lebih baru', version_compare(PHP_VERSION, '8.3.0', '>='), 'pilih PHP 8.3+ di MultiPHP Manager');
    foreach (['pdo_mysql', 'mbstring', 'intl', 'zip', 'xml', 'dom', 'openssl', 'fileinfo', 'curl'] as $ext) {
        $cek("Ekstensi {$ext}", extension_loaded($ext), 'aktifkan di Select PHP Version › Extensions');
    }
    $cek('APP_KEY terisi', filled(config('app.key')), 'jalankan php artisan key:generate');
    $cek('APP_DEBUG mati di produksi', ! (app()->isProduction() && config('app.debug')), 'set APP_DEBUG=false');
    try {
        DB::connection()->getPdo();
        $versi = DB::selectOne(DB::getDriverName() === 'sqlite' ? 'select sqlite_version() as v' : 'select version() as v')->v ?? '';
        $cek('Koneksi database ('.DB::getDriverName().' '.$versi.')', true);
    } catch (Throwable $e) {
        $cek('Koneksi database', false, $e->getMessage());
    }
    $cek('Folder storage dapat ditulis', is_writable(storage_path('app')) && is_writable(storage_path('framework')), 'chmod 775 storage bootstrap/cache');
    $cek('Penyimpanan privat tidak di public_html', ! str_starts_with(Storage::disk('local')->path(''), public_path()), 'jangan letakkan storage di dalam public');
    $cek('Antrean memakai database', config('queue.default') === 'database' || config('queue.default') === 'sync');
    $cek('Jabatan notaris terisi', rescue(fn () => Jabatan::query()->exists(), false, false), 'isi di menu Pengaturan › Jabatan Notaris');
    $cek('Rantai jejak audit utuh', rescue(fn () => AuditLog::verifyChain() === null, false, false), 'jalankan notaria:periksa-audit');
    $this->line(filled(config('notaria.ai.api_key')) ? '  ..  Kunci OpenAI terisi (fitur AI tetap mati sampai dinyalakan notaris).' : '  ..  Kunci OpenAI kosong: fitur AI tidak tersedia.');

    return $ok ? 0 : 1;
})->purpose('Periksa kesiapan server');

// Tanggal 1 setiap bulan: tenggat laporan MPD, daftar wasiat, dan penjilidan untuk bulan sebelumnya.
Schedule::command('notaria:tenggat-bulanan')->monthlyOn(1, '00:30');
Schedule::command('notaria:periksa-audit')->weeklyOn(1, '01:00');
if (config('notaria.demo.enabled') && config('notaria.demo.reset_harian')) {
    Schedule::command('notaria:demo-reset')->dailyAt('02:00');
}
Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();
