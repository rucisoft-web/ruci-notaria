<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AktaSaksi extends Model
{
    protected $table = 'akta_saksi';

    protected $guarded = ['id'];

    public const JENIS = ['akta' => 'Saksi akta', 'pengenal' => 'Saksi pengenal'];

    protected function casts(): array
    {
        return ['potret' => 'encrypted:array'];
    }

    public function akta(): BelongsTo
    {
        return $this->belongsTo(Akta::class);
    }

    public function subjek(): BelongsTo
    {
        return $this->belongsTo(Subjek::class);
    }

    public function data(): array
    {
        return $this->potret ?: $this->subjek->potret();
    }
}
