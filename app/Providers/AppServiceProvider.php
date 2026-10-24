<?php

namespace App\Providers;

use App\Support\TamuHanyaLihat;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('id');
        TamuHanyaLihat::daftarkan();
    }
}
