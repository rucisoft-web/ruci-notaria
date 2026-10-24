<?php

namespace App\Models;

use App\Enums\Hubungan;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * Klien, penghadap, saksi, atau badan. NIK/nomor paspor dan NPWP disimpan terenkripsi;
 * pencarian NIK memakai nik_hash (HMAC dengan APP_KEY) sehingga nilai aslinya tidak pernah ada di indeks.
 */
class Subjek extends Model
{
    use Auditable;

    protected $table = 'subjek';

    protected $guarded = ['id', 'nik_hash'];

    /** Kolom yang nilai lama/barunya dicatat. NIK tidak dicatat nilainya. */
    public array $tracked = ['nama', 'tanggal_lahir', 'status_perkawinan', 'alamat', 'kewarganegaraan'];

    protected function casts(): array
    {
        return [
            'nik' => 'encrypted',
            'npwp' => 'encrypted',
            'tanggal_lahir' => 'date',
            'identitas_berlaku_sampai' => 'date',
            'saksi_kantor' => 'boolean',
            'persetujuan_ai' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $subjek) {
            if ($subjek->isDirty('nik')) {
                $subjek->nik_hash = static::hashNik($subjek->nik);
            }
        });
    }

    public static function hashNik(?string $nik): ?string
    {
        $nik = preg_replace('/\s+/', '', (string) $nik);

        return $nik === '' ? null : hash_hmac('sha256', strtoupper($nik), (string) config('app.key'));
    }

    public function scopeCari(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q->where('nama', 'like', "%{$term}%")
            ->orWhere('nik_hash', static::hashNik($term))
            ->orWhere('telepon', 'like', "%{$term}%"));
    }

    public function isBadan(): bool
    {
        return $this->jenis === 'badan';
    }

    public function umurPada(\DateTimeInterface $tanggal): ?int
    {
        return $this->tanggal_lahir ? (int) floor($this->tanggal_lahir->diffInYears(Carbon::instance($tanggal), true)) : null;
    }

    /** Alamat lengkap untuk komparisi akta. */
    public function alamatLengkap(): string
    {
        return collect([
            $this->alamat,
            $this->rt_rw ? 'RT/RW '.$this->rt_rw : null,
            $this->kelurahan ? 'Kelurahan/Desa '.$this->kelurahan : null,
            $this->kecamatan ? 'Kecamatan '.$this->kecamatan : null,
            $this->kota, $this->provinsi,
        ])->filter()->implode(', ');
    }

    /** NIK disamarkan untuk tampilan daftar: 3273**********01. */
    public function nikTersamar(): ?string
    {
        $nik = (string) $this->nik;

        return $nik === '' ? null : substr($nik, 0, 4).str_repeat('*', max(0, strlen($nik) - 6)).substr($nik, -2);
    }

    public function relasi(): HasMany
    {
        return $this->hasMany(RelasiSubjek::class, 'subjek_id');
    }

    public function relasiMasuk(): HasMany
    {
        return $this->hasMany(RelasiSubjek::class, 'terkait_id');
    }

    /** Hubungan langsung antara dua subjek (dicari dua arah), dari sudut pandang $this. */
    public function hubunganDengan(Subjek|int $other): ?Hubungan
    {
        $otherId = $other instanceof Subjek ? $other->id : $other;

        $out = RelasiSubjek::query()->where('subjek_id', $this->id)->where('terkait_id', $otherId)->value('hubungan');
        if ($out) {
            return $out instanceof Hubungan ? $out : Hubungan::from($out);
        }

        $in = RelasiSubjek::query()->where('subjek_id', $otherId)->where('terkait_id', $this->id)->value('hubungan');

        return $in ? ($in instanceof Hubungan ? $in : Hubungan::from($in))->kebalikan() : null;
    }

    public function dokumen(): MorphMany
    {
        return $this->morphMany(Dokumen::class, 'dokumenable');
    }

    public function aktaPenghadap(): HasMany
    {
        return $this->hasMany(AktaPenghadap::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Data yang dibekukan ke akta saat penomoran. */
    public function potret(): array
    {
        return $this->only([
            'jenis', 'nama', 'nik', 'jenis_identitas', 'tempat_lahir', 'jenis_kelamin', 'kewarganegaraan', 'pekerjaan',
            'status_perkawinan', 'alamat', 'rt_rw', 'kelurahan', 'kecamatan', 'kota', 'provinsi', 'bentuk_badan', 'nomor_sk_badan', 'nib',
        ]) + ['tanggal_lahir' => $this->tanggal_lahir?->toDateString()];
    }

    public static function saksiOptions(): array
    {
        return static::query()->where('saksi_kantor', true)->orderBy('nama')->pluck('nama', 'id')->all();
    }
}
