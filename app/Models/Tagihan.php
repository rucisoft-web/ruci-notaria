<?php

namespace App\Models;

use App\Enums\StatusTagihan;
use App\Models\Concerns\Auditable;
use App\Support\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tagihan extends Model
{
    use Auditable;

    protected $table = 'tagihan';

    protected $guarded = ['id', 'nomor'];

    public array $tracked = ['status'];

    protected function casts(): array
    {
        return ['status' => StatusTagihan::class, 'tanggal' => 'date', 'jatuh_tempo' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $tagihan) {
            $tagihan->nomor ??= Sequence::next('INV');
            $tagihan->tanggal ??= today();
            // Diisi di model (bukan hanya default kolom) agar status langsung terbaca setelah dibuat.
            $tagihan->status ??= StatusTagihan::Draf;
            $tagihan->created_by ??= auth()->id();
        });
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TagihanItem::class);
    }

    public function pembayaran(): HasMany
    {
        return $this->hasMany(Pembayaran::class);
    }

    public function total(): float
    {
        return (float) $this->items()->sum('jumlah');
    }

    public function totalHonorarium(): float
    {
        return (float) $this->items()->where('jenis', 'honorarium')->sum('jumlah');
    }

    public function terbayar(): float
    {
        return (float) $this->pembayaran()->sum('jumlah');
    }

    public function sisa(): float
    {
        return round($this->total() - $this->terbayar(), 2);
    }

    /** Status lunas mengikuti pembayaran. */
    public function sinkronStatus(): void
    {
        if ($this->status === StatusTagihan::Batal || $this->status === StatusTagihan::Draf) {
            return;
        }
        $this->update(['status' => $this->sisa() <= 0 ? StatusTagihan::Lunas : StatusTagihan::Terbit]);
    }
}
