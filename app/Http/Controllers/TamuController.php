<?php

namespace App\Http\Controllers;

use App\Models\Jabatan;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** "Masuk sebagai tamu" di situs demo: langsung masuk ke akun notaris contoh tanpa sandi. */
class TamuController extends Controller
{
    public const EMAIL = 'tamu@notaria.demo';

    /** Kunci sesi notaris contoh yang dipilih tamu (lihat Jabatan::utama()). */
    public const SESI_NOTARIS = 'tamu.jabatan_id';

    public static function tersedia(): bool
    {
        return config('notaria.demo.enabled') && config('notaria.demo.tamu');
    }

    public function masuk(Request $request): RedirectResponse
    {
        abort_unless(static::tersedia(), 404);
        $tamu = User::query()->where('email', self::EMAIL)->where('is_tamu', true)->where('is_active', true)->first();
        abort_unless($tamu, 404, 'Akun tamu belum dibuat. Jalankan php artisan notaria:demo.');

        Auth::login($tamu);
        $request->session()->regenerate();
        $this->pilih($request, $request->string('notaris')->toString());

        return redirect('/app');
    }

    /** Mengganti notaris contoh dari pita tamu di atas panel. */
    public function ganti(Request $request, string $kode): RedirectResponse
    {
        abort_unless(static::tersedia() && $request->user()?->isTamu(), 404);
        $this->pilih($request, $kode);

        return redirect()->back(fallback: '/app');
    }

    /** @return Collection<int, Jabatan> */
    public static function notarisContoh(): Collection
    {
        return Jabatan::query()->where('is_active', true)->where('jenis', 'notaris')->orderBy('id')->get();
    }

    private function pilih(Request $request, string $kode): void
    {
        if ($jabatan = static::notarisContoh()->firstWhere('kode', $kode)) {
            $request->session()->put(self::SESI_NOTARIS, $jabatan->id);
        }
    }
}
