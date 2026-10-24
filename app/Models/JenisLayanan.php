<?php

namespace App\Models;

use App\Enums\KategoriAkta;
use App\Enums\SifatAkta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Katalog layanan: daftar periksa dokumen, aturan tenggat, dan biaya bawaan. */
class JenisLayanan extends Model
{
    protected $table = 'jenis_layanan';

    protected $guarded = ['id'];

    public const KATEGORI = [
        'akta' => 'Akta notaris', 'legalisasi' => 'Legalisasi', 'waarmerking' => 'Waarmerking',
        'fotokopi' => 'Pengesahan fotokopi', 'ppat' => 'PPAT', 'lainnya' => 'Lainnya',
    ];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'aturan_tenggat' => 'array',
            'kategori_akta' => KategoriAkta::class,
            'sifat_akta' => SifatAkta::class,
            'is_active' => 'boolean',
            'honorarium_bawaan' => 'decimal:2',
        ];
    }

    public function berkas(): HasMany
    {
        return $this->hasMany(Berkas::class);
    }

    public static function options(): array
    {
        return static::query()->where('is_active', true)->orderBy('urutan')->orderBy('nama')->pluck('nama', 'id')->all();
    }
}
