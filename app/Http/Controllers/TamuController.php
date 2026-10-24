<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** "Masuk sebagai tamu" di situs demo: langsung masuk ke akun notaris contoh tanpa sandi. */
class TamuController extends Controller
{
    public const EMAIL = 'tamu@notaria.demo';

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

        return redirect('/app');
    }
}
