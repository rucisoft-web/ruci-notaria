<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Jabatan notaris (pemegang SK) atau notaris pengganti. */
class Jabatan extends Model
{
    use Auditable;

    protected $table = 'jabatan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tanggal_sk' => 'date', 'tanggal_sumpah' => 'date', 'tanggal_lahir' => 'date',
            'cuti_mulai' => 'date', 'cuti_selesai' => 'date', 'is_ppat' => 'boolean', 'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subjek(): BelongsTo
    {
        return $this->belongsTo(Subjek::class);
    }

    public function pengganti(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'pengganti_id');
    }

    public function akta(): HasMany
    {
        return $this->hasMany(Akta::class);
    }

    public function auditLabel(): string
    {
        return $this->nama_lengkap;
    }

    public function sedangCuti(?\DateTimeInterface $date = null): bool
    {
        $date = $date ? Carbon::instance($date) : now();

        return $this->cuti_mulai && $this->cuti_selesai && $date->betweenIncluded($this->cuti_mulai->startOfDay(), $this->cuti_selesai->endOfDay());
    }

    /** Tanggal usia 65 tahun (Pasal 8 UUJN). */
    public function batasUsia(): ?Carbon
    {
        return $this->tanggal_lahir?->copy()->addYears(65);
    }

    public static function options(): array
    {
        return static::query()->where('is_active', true)->orderBy('id')->pluck('nama_lengkap', 'id')->all();
    }

    public static function utama(): ?self
    {
        return static::query()->where('is_active', true)->where('jenis', 'notaris')->orderBy('id')->first();
    }
}
