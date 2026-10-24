<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

/**
 * Jejak audit dengan rantai hash: setiap baris menyimpan sha256(hash baris sebelumnya + isinya),
 * sehingga penghapusan atau perubahan langsung di database terdeteksi oleh `notaria:periksa-audit`.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /** Dimatikan saat memuat data contoh. */
    public static bool $enabled = true;

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    public static function record(string $event, Model $model, ?array $changes = null): void
    {
        if (! static::$enabled) {
            return;
        }

        DB::transaction(function () use ($event, $model, $changes) {
            $previous = static::query()->latest('id')->lockForUpdate()->value('hash') ?? '';
            $row = [
                'user_id' => auth()->id(),
                'event' => $event,
                'auditable_type' => $model->getMorphClass(),
                'auditable_id' => $model->getKey(),
                'label' => method_exists($model, 'auditLabel') ? mb_substr($model->auditLabel(), 0, 250) : null,
                'changes' => $changes,
                'ip_address' => request()?->ip(),
                'created_at' => now(),
            ];
            $row['hash'] = static::hashFor($previous, $row);
            static::create($row);
        });
    }

    public static function hashFor(string $previous, array $row): string
    {
        $created = $row['created_at'] instanceof \DateTimeInterface ? $row['created_at']->format('Y-m-d H:i:s') : (string) $row['created_at'];

        return hash('sha256', $previous.'|'.implode('|', [
            $row['user_id'], $row['event'], $row['auditable_type'], $row['auditable_id'], $row['label'],
            json_encode($row['changes']), $created,
        ]));
    }

    /** @return int|null id baris pertama yang rantainya putus, atau null bila utuh. */
    public static function verifyChain(): ?int
    {
        $previous = '';

        foreach (static::query()->orderBy('id')->cursor() as $log) {
            // getAttribute: di dalam kelas ini $log->changes menunjuk properti internal Model, bukan kolom.
            $expected = static::hashFor($previous, [
                'user_id' => $log->user_id, 'event' => $log->event, 'auditable_type' => $log->auditable_type,
                'auditable_id' => $log->auditable_id, 'label' => $log->label,
                'changes' => $log->getAttribute('changes'), 'created_at' => $log->created_at,
            ]);

            if (! hash_equals($expected, (string) $log->hash)) {
                return $log->id;
            }
            $previous = $log->hash;
        }

        return null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function typeLabel(): string
    {
        return match (class_basename($this->auditable_type)) {
            'Akta' => 'Akta',
            'Berkas' => 'Berkas',
            'Subjek' => 'Klien/penghadap',
            'SuratBawahTangan' => 'Legalisasi/waarmerking',
            'Salinan' => 'Salinan',
            'TemplateAkta' => 'Template',
            'Tagihan' => 'Tagihan',
            'Pembayaran' => 'Pembayaran',
            'DanaTitipan' => 'Dana titipan',
            'Dokumen' => 'Dokumen',
            default => class_basename($this->auditable_type),
        };
    }
}
