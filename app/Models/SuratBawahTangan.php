<?php

namespace App\Models;

use App\Enums\JenisSurat;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Legalisasi, waarmerking, dan pengesahan fotokopi. Nomor diberikan oleh App\Services\PenomoranSurat
 * saat dicatat; entri tidak dapat dihapus, hanya ditandai batal dengan alasan.
 */
class SuratBawahTangan extends Model
{
    use Auditable;

    protected $table = 'surat_bawah_tangan';

    protected $guarded = ['id', 'tahun', 'nomor_urut', 'nomor'];

    public array $tracked = ['judul', 'para_pihak', 'tanggal', 'batal', 'halaman_buku'];

    protected function casts(): array
    {
        return ['jenis' => JenisSurat::class, 'tanggal' => 'date', 'tanggal_surat' => 'date', 'batal' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $surat) {
            foreach (['jabatan_id', 'jenis', 'tahun', 'nomor_urut', 'nomor'] as $field) {
                if ($surat->isDirty($field)) {
                    throw new \LogicException("Nomor {$surat->getOriginal('nomor')} sudah tercatat; kolom {$field} tidak dapat diubah.");
                }
            }
        });
        static::deleting(fn () => throw new \LogicException('Entri buku daftar tidak dapat dihapus; tandai batal.'));
    }

    public function scopeTerlihatOleh(Builder $query, ?User $user): Builder
    {
        return $user?->role?->canViewFiles() ? $query : $query->whereRaw('1 = 0');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    public function auditLabel(): string
    {
        return $this->nomor.' '.$this->judul;
    }
}
