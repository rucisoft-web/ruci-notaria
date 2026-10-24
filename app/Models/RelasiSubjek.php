<?php

namespace App\Models;

use App\Enums\Hubungan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelasiSubjek extends Model
{
    protected $table = 'relasi_subjek';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['hubungan' => Hubungan::class];
    }

    public function subjek(): BelongsTo
    {
        return $this->belongsTo(Subjek::class);
    }

    public function terkait(): BelongsTo
    {
        return $this->belongsTo(Subjek::class, 'terkait_id');
    }
}
