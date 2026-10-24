<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LokasiArsip extends Model
{
    protected $table = 'lokasi_arsip';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function jilid(): HasMany
    {
        return $this->hasMany(JilidMinuta::class);
    }

    public static function options(): array
    {
        return static::query()->where('is_active', true)->orderBy('kode')->get()->mapWithKeys(fn ($l) => [$l->id => $l->kode.' — '.$l->nama])->all();
    }
}
