<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pembetulan kesalahan tulis/ketik pada minuta yang telah ditandatangani (Pasal 51 UUJN). */
class BeritaAcaraPembetulan extends Model
{
    use Auditable;

    protected $table = 'berita_acara_pembetulan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $ba) => $ba->created_by ??= auth()->id());
        static::deleting(fn () => throw new \LogicException('Berita acara pembetulan tidak dapat dihapus.'));
    }

    public function akta(): BelongsTo
    {
        return $this->belongsTo(Akta::class);
    }

    public function auditLabel(): string
    {
        return 'BA pembetulan akta #'.$this->akta_id;
    }
}
