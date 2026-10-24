<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Log setiap panggilan AI: siapa, fitur apa, berapa token dan biaya, dan keputusan pengguna. */
class AiPermintaan extends Model
{
    protected $table = 'ai_permintaan';

    protected $guarded = ['id'];

    public const FITUR = ['ekstraksi' => 'Ekstraksi dokumen identitas', 'pemeriksa' => 'Pemeriksa draf akta'];

    protected function casts(): array
    {
        return ['biaya_usd' => 'decimal:5'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    public function subjekAi(): MorphTo
    {
        return $this->morphTo();
    }

    public static function biayaBulanIni(): float
    {
        return (float) static::query()->where('created_at', '>=', now()->startOfMonth())->sum('biaya_usd');
    }
}
