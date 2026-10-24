<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Pengaturan kantor dalam bentuk kunci-nilai (nama kantor, AI, format nomor). */
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'array'];
    }

    public const DEFAULTS = [
        'kantor.nama' => 'Kantor Notaris',
        'kantor.zona_waktu' => 'WIB',
        'kantor.kota' => null,
        'kantor.telepon' => null,
        'nomor.berkas' => 'BRK',
        'tenggat.wasiat_hari' => 5,
        'tenggat.jilid_hari' => 10,
        'ai.aktif' => false,
        'ai.ekstraksi' => false,
        'ai.pemeriksa' => false,
        'ai.batas_bulanan_usd' => 20,
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('pengaturan', fn () => static::query()->get()->mapWithKeys(fn (self $row) => [$row->key => $row->value])->all());

        return array_key_exists($key, $all) ? $all[$key] : ($default ?? (static::DEFAULTS[$key] ?? null));
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('pengaturan');
    }
}
