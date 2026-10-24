<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Mutasi dana titipan klien (PNBP, BPHTB, PPh, biaya BPN). Hanya bertambah; koreksi dengan mutasi balik. */
class DanaTitipan extends Model
{
    use Auditable;

    protected $table = 'dana_titipan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'jumlah' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->created_by ??= auth()->id());
        static::updating(fn () => throw new \LogicException('Mutasi dana titipan tidak dapat diubah; catat mutasi balik.'));
        static::deleting(fn () => throw new \LogicException('Mutasi dana titipan tidak dapat dihapus; catat mutasi balik.'));
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    public function auditLabel(): string
    {
        return $this->arah.' '.$this->jumlah.' '.$this->uraian;
    }
}
