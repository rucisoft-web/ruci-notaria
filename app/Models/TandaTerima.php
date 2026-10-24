<?php

namespace App\Models;

use App\Support\Sequence;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Tanda terima dokumen asli yang masuk dari atau diserahkan kepada klien. */
class TandaTerima extends Model
{
    protected $table = 'tanda_terima';

    protected $guarded = ['id', 'nomor'];

    protected function casts(): array
    {
        return ['tanggal' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $tt) {
            $tt->nomor ??= Sequence::next('TT');
            $tt->created_by ??= auth()->id();
        });
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
