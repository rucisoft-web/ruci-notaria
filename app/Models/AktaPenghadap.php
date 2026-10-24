<?php

namespace App\Models;

use App\Enums\Kedudukan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AktaPenghadap extends Model
{
    protected $table = 'akta_penghadap';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['kedudukan' => Kedudukan::class, 'sidik_jari' => 'boolean', 'potret' => 'encrypted:array'];
    }

    public function akta(): BelongsTo
    {
        return $this->belongsTo(Akta::class);
    }

    public function subjek(): BelongsTo
    {
        return $this->belongsTo(Subjek::class);
    }

    public function mewakili(): BelongsTo
    {
        return $this->belongsTo(Subjek::class, 'mewakili_id');
    }

    /** Data penghadap: potret bila akta sudah bernomor, data profil terkini bila belum. */
    public function data(): array
    {
        return $this->potret ?: $this->subjek->potret();
    }
}
