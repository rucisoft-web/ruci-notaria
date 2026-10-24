<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TagihanItem extends Model
{
    protected $table = 'tagihan_item';

    protected $guarded = ['id'];

    public const JENIS = ['honorarium' => 'Honorarium', 'titipan' => 'Titipan pihak ketiga (PNBP, pajak, BPN)', 'lain' => 'Biaya lain'];

    protected function casts(): array
    {
        return ['jumlah' => 'decimal:2'];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }
}
