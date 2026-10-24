<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BerkasPihak extends Model
{
    protected $table = 'berkas_pihak';

    protected $guarded = ['id'];

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    public function subjek(): BelongsTo
    {
        return $this->belongsTo(Subjek::class);
    }
}
