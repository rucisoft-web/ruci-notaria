<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tenggat extends Model
{
    protected $table = 'tenggat';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['jatuh_tempo' => 'date', 'selesai_at' => 'datetime'];
    }

    public function scopeTerbuka(Builder $query): Builder
    {
        return $query->whereNull('selesai_at');
    }

    public function scopeTerlambat(Builder $query): Builder
    {
        return $query->whereNull('selesai_at')->whereDate('jatuh_tempo', '<', today());
    }

    public function status(): string
    {
        return match (true) {
            $this->selesai_at !== null => 'selesai',
            $this->jatuh_tempo->lt(today()) => 'terlambat',
            $this->jatuh_tempo->lte(today()->addDays(7)) => 'segera',
            default => 'terjadwal',
        };
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    public function akta(): BelongsTo
    {
        return $this->belongsTo(Akta::class);
    }

    public function selesaiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selesai_oleh');
    }
}
