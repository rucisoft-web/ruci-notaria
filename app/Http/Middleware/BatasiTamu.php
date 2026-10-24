<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Akun tamu dipakai bersama banyak pengunjung, jadi profil (nama, sandi, MFA) tidak boleh diubah. */
class BatasiTamu
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->isTamu() && $request->routeIs('filament.*.auth.profile')) {
            abort(403, 'Profil akun tamu tidak dapat diubah.');
        }

        return $next($request);
    }
}
