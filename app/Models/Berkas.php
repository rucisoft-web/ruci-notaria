<?php

namespace App\Models;

use App\Enums\Role;
use App\Enums\StatusBerkas;
use App\Models\Concerns\Auditable;
use App\Support\Sequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Satu order klien. Pusat dari pihak, dokumen, akta, tugas, tagihan, dan dana titipan. */
class Berkas extends Model
{
    use Auditable;

    protected $table = 'berkas';

    protected $guarded = ['id', 'nomor'];

    public array $tracked = ['status', 'pic_id', 'sangat_rahasia', 'nilai_transaksi'];

    protected function casts(): array
    {
        return [
            'status' => StatusBerkas::class,
            'sangat_rahasia' => 'boolean',
            'cuma_cuma' => 'boolean',
            'nilai_transaksi' => 'decimal:2',
            'tanggal_masuk' => 'date',
            'tanggal_selesai' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $berkas) {
            $berkas->nomor ??= Sequence::next(Pengaturan::get('nomor.berkas', 'BRK'));
            $berkas->tanggal_masuk ??= now()->toDateString();
            $berkas->status ??= StatusBerkas::Baru;
            $berkas->created_by ??= auth()->id();
        });

        // Daftar periksa dokumen diisi dari jenis layanan saat berkas dibuat.
        static::created(function (self $berkas) {
            foreach ((array) $berkas->jenisLayanan?->checklist as $i => $nama) {
                $berkas->checklist()->create(['nama' => $nama, 'urutan' => $i]);
            }
        });
    }

    /**
     * Berkas bertanda sangat rahasia hanya terlihat oleh notaris, PIC, dan anggota berkas.
     * Peran admin TI tidak melihat isi berkas sama sekali.
     */
    public function scopeTerlihatOleh(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->role === Role::Admin) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->role === Role::Notaris) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->where('sangat_rahasia', false)
            ->orWhere('pic_id', $user->id)
            ->orWhereHas('anggota', fn (Builder $a) => $a->where('users.id', $user->id)));
    }

    public function jenisLayanan(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function klien(): BelongsTo
    {
        return $this->belongsTo(Subjek::class, 'klien_id');
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function anggota(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'berkas_anggota');
    }

    public function pihak(): HasMany
    {
        return $this->hasMany(BerkasPihak::class);
    }

    public function checklist(): HasMany
    {
        return $this->hasMany(BerkasChecklist::class)->orderBy('urutan');
    }

    public function dokumen(): MorphMany
    {
        return $this->morphMany(Dokumen::class, 'dokumenable');
    }

    public function akta(): HasMany
    {
        return $this->hasMany(Akta::class);
    }

    public function tugas(): HasMany
    {
        return $this->hasMany(Tugas::class);
    }

    public function tandaTerima(): HasMany
    {
        return $this->hasMany(TandaTerima::class);
    }

    public function tagihan(): HasMany
    {
        return $this->hasMany(Tagihan::class);
    }

    public function danaTitipan(): HasMany
    {
        return $this->hasMany(DanaTitipan::class);
    }

    public function tenggat(): HasMany
    {
        return $this->hasMany(Tenggat::class);
    }

    public function saldoTitipan(): float
    {
        return (float) $this->danaTitipan()->selectRaw("COALESCE(SUM(CASE WHEN arah = 'masuk' THEN jumlah ELSE -jumlah END), 0) as saldo")->value('saldo');
    }

    public function dokumenKurang(): int
    {
        return $this->checklist()->whereIn('status', ['diminta', 'ditolak'])->count();
    }
}
