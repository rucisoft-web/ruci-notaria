<?php

use App\Http\Controllers\CetakController;
use App\Http\Controllers\UnduhController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/app');
Route::get('/login', fn () => redirect('/app/login'))->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/unduh/dokumen/{dokumen}', [UnduhController::class, 'dokumen'])->name('dokumen.unduh');
    Route::get('/unduh/draf/{draf}', [UnduhController::class, 'draf'])->name('draf.unduh');

    Route::prefix('cetak')->name('cetak.')->controller(CetakController::class)->group(function () {
        Route::get('/buku/{jenis}/{jabatan}/{tahun}/{bulan}', 'buku')->name('buku')->whereNumber(['tahun', 'bulan']);
        Route::get('/laporan-mpd/{jabatan}/{tahun}/{bulan}', 'laporanMpd')->name('laporan-mpd')->whereNumber(['tahun', 'bulan']);
        Route::get('/tanda-terima/{tandaTerima}', 'tandaTerima')->name('tanda-terima');
        Route::get('/lembar-ttd/{akta}', 'lembarTtd')->name('lembar-ttd');
        Route::get('/pengesahan/{surat}', 'pengesahan')->name('pengesahan');
        Route::get('/label-jilid/{jilid}', 'labelJilid')->name('label-jilid');
        Route::get('/tagihan/{tagihan}', 'tagihan')->name('tagihan');
        Route::get('/kuitansi/{pembayaran}', 'kuitansi')->name('kuitansi');
    });
});
