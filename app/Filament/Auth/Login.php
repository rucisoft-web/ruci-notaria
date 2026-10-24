<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\Login as LoginBawaan;
use Illuminate\Contracts\Support\Htmlable;

/** Halaman masuk dengan panel merek di kiri (lihat filament.masuk-merek) dan judul khusus. */
class Login extends LoginBawaan
{
    public function getHeading(): string|Htmlable|null
    {
        return filled($this->userUndertakingMultiFactorAuthentication) ? parent::getHeading() : 'Masuk ke Ruci Notaria';
    }

    public function getSubheading(): string|Htmlable|null
    {
        return filled($this->userUndertakingMultiFactorAuthentication) ? parent::getSubheading() : 'Gunakan akun yang diberikan notaris atau admin kantor Anda.';
    }
}
