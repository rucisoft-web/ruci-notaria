<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Support\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pembayaran extends Model
{
    use Auditable;

    protected $table = 'pembayaran';

    protected $guarded = ['id', 'nomor_kuitansi'];

    public const METODE = ['tunai' => 'Tunai', 'transfer' => 'Transfer bank', 'qris' => 'QRIS', 'lainnya' => 'Lainnya'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'jumlah' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $p) {
            $p->nomor_kuitansi ??= Sequence::next('KWT');
            $p->created_by ??= auth()->id();
        });
        static::created(fn (self $p) => $p->tagihan->sinkronStatus());
        static::deleting(fn () => throw new \LogicException('Pembayaran tidak dapat dihapus; catat koreksi.'));
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function auditLabel(): string
    {
        return $this->nomor_kuitansi;
    }
}
