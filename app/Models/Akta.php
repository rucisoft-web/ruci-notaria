<?php

namespace App\Models;

use App\Enums\KategoriAkta;
use App\Enums\SifatAkta;
use App\Enums\StatusAkta;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * Akta notaris. Nomor, tanggal, dan jam diberikan oleh App\Services\PenomoranAkta saat penandatanganan dicatat
 * dan sesudahnya tidak dapat diubah; akta bernomor tidak dapat dihapus (koreksi lewat berita acara pembetulan).
 */
class Akta extends Model
{
    use Auditable;

    protected $table = 'akta';

    protected $guarded = ['id', 'tahun', 'bulan', 'nomor_bulanan', 'nomor_urut', 'tanggal', 'jam', 'dinomori_oleh', 'dinomori_at'];

    public array $tracked = ['status', 'jadwal_at', 'judul', 'dibacakan', 'renvoi_tambah', 'renvoi_coret', 'renvoi_ganti', 'jilid_minuta_id', 'halaman_buku'];

    /** Kolom yang terkunci setelah akta bernomor. */
    public const TERKUNCI = ['jabatan_id', 'tahun', 'bulan', 'nomor_bulanan', 'nomor_urut', 'tanggal', 'jam', 'sifat', 'kategori', 'judul', 'berkas_id'];

    protected function casts(): array
    {
        return [
            'status' => StatusAkta::class,
            'sifat' => SifatAkta::class,
            'kategori' => KategoriAkta::class,
            'jadwal_at' => 'datetime',
            'tanggal' => 'date',
            'dinomori_at' => 'datetime',
            'dibacakan' => 'boolean',
            'minuta_lampiran_lengkap' => 'boolean',
            'minuta_sidik_jari_lengkap' => 'boolean',
            'nilai_ekonomis' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $akta) {
            $akta->created_by ??= auth()->id();
            $akta->jabatan_id ??= $akta->berkas?->jabatan_id;
            $layanan = $akta->jenisLayanan ?? $akta->berkas?->jenisLayanan;
            $akta->kategori ??= $layanan?->kategori_akta ?? KategoriAkta::Umum;
            $akta->sifat ??= $layanan?->sifat_akta ?? SifatAkta::Minuta;
            $akta->status ??= StatusAkta::Draf;
        });

        static::updating(function (self $akta) {
            if ($akta->getOriginal('nomor_urut') !== null) {
                foreach (self::TERKUNCI as $field) {
                    if ($akta->isDirty($field)) {
                        throw new LogicException("Akta sudah bernomor; kolom {$field} tidak dapat diubah. Gunakan berita acara pembetulan.");
                    }
                }
                if ($akta->isDirty('status')) {
                    throw new LogicException('Status akta bernomor tidak dapat diubah.');
                }
            }
        });

        static::deleting(function (self $akta) {
            if ($akta->nomor_urut !== null) {
                throw new LogicException('Akta bernomor tidak dapat dihapus.');
            }
        });
    }

    public function scopeTerlihatOleh(Builder $query, ?User $user): Builder
    {
        return $query->whereHas('berkas', fn (Builder $b) => $b->terlihatOleh($user));
    }

    public function scopeBernomor(Builder $query): Builder
    {
        return $query->whereNotNull('nomor_urut');
    }

    public function scopePeriode(Builder $query, int $jabatanId, int $tahun, ?int $bulan = null): Builder
    {
        return $query->where('jabatan_id', $jabatanId)->where('tahun', $tahun)->when($bulan, fn ($q) => $q->where('bulan', $bulan));
    }

    public function isBernomor(): bool
    {
        return $this->nomor_urut !== null;
    }

    public function jumlahRenvoi(): int
    {
        return (int) $this->renvoi_tambah + (int) $this->renvoi_coret + (int) $this->renvoi_ganti;
    }

    public function auditLabel(): string
    {
        return trim(($this->nomor_bulanan ? 'No. '.$this->nomor_bulanan.' ' : '').$this->judul);
    }

    public function label(): string
    {
        return $this->isBernomor()
            ? sprintf('No. %d, %s — %s', $this->nomor_bulanan, $this->tanggal->translatedFormat('d F Y'), $this->judul)
            : $this->judul.' (belum bernomor)';
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function jenisLayanan(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class);
    }

    public function penghadap(): HasMany
    {
        return $this->hasMany(AktaPenghadap::class)->orderBy('urutan')->orderBy('id');
    }

    public function saksi(): HasMany
    {
        return $this->hasMany(AktaSaksi::class);
    }

    public function draf(): HasMany
    {
        return $this->hasMany(DrafAkta::class)->orderByDesc('versi');
    }

    public function drafTerakhir(): HasOne
    {
        return $this->hasOne(DrafAkta::class)->latestOfMany('versi');
    }

    public function salinan(): HasMany
    {
        return $this->hasMany(Salinan::class);
    }

    public function beritaAcaraPembetulan(): HasMany
    {
        return $this->hasMany(BeritaAcaraPembetulan::class);
    }

    public function peminjaman(): HasMany
    {
        return $this->hasMany(PeminjamanMinuta::class);
    }

    public function jilid(): BelongsTo
    {
        return $this->belongsTo(JilidMinuta::class, 'jilid_minuta_id');
    }

    public function bukuFisik(): BelongsTo
    {
        return $this->belongsTo(BukuFisik::class);
    }

    public function dinomoriOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dinomori_oleh');
    }
}
