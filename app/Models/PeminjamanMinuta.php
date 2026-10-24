<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeminjamanMinuta extends Model
{
    protected $table = 'peminjaman_minuta';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['dipinjam_at' => 'datetime', 'jatuh_tempo' => 'date', 'dikembalikan_at' => 'datetime'];
    }

    public function akta(): BelongsTo
    {
        return $this->belongsTo(Akta::class);
    }

    public function peminjam(): BelongsTo
    {
        return $this->belongsTo(User::class, 'peminjam_id');
    }
}
