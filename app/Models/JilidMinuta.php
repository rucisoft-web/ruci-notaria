<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Satu buku jilid minuta: paling banyak 50 akta dari satu bulan (Pasal 16 ayat 1 huruf g UUJN). */
class JilidMinuta extends Model
{
    protected $table = 'jilid_minuta';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['dijilid_at' => 'datetime'];
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(LokasiArsip::class, 'lokasi_arsip_id');
    }

    public function akta(): HasMany
    {
        return $this->hasMany(Akta::class);
    }

    public function label(): string
    {
        return sprintf('%02d/%d jilid %d (No. %d–%d)', $this->bulan, $this->tahun, $this->nomor_jilid, $this->nomor_awal, $this->nomor_akhir);
    }
}
