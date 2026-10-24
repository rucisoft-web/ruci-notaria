<?php

namespace App\Models;

use App\Enums\JenisDaftar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Buku daftar fisik yang telah diberi nomor halaman dan diparaf MPD (Pasal 58 ayat 7 UUJN). */
class BukuFisik extends Model
{
    protected $table = 'buku_fisik';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['jenis_daftar' => JenisDaftar::class, 'tanggal_paraf_mpd' => 'date', 'mulai_dipakai' => 'date', 'aktif' => 'boolean'];
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function label(): string
    {
        return $this->jenis_daftar->getLabel().' — buku '.$this->nomor_buku;
    }
}
