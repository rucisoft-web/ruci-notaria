<?php

namespace App\Models;

use App\Enums\DasarHak;
use App\Enums\JenisSalinan;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Register salinan, kutipan, dan grosse beserta dasar hak penerimanya (Pasal 54 UUJN). */
class Salinan extends Model
{
    use Auditable;

    protected $table = 'salinan';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['jenis' => JenisSalinan::class, 'dasar_hak' => DasarHak::class, 'tanggal' => 'date', 'ditolak' => 'boolean', 'bermeterai' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $salinan) {
            $salinan->created_by ??= auth()->id();
            $salinan->ditolak = $salinan->dasar_hak === DasarHak::TidakBerhak;

            if ($salinan->jenis === JenisSalinan::Grosse && ! $salinan->ditolak) {
                $salinan->grosse_ke = static::query()->where('akta_id', $salinan->akta_id)->where('jenis', 'grosse')->where('ditolak', false)->count() + 1;
            }
        });
    }

    public function akta(): BelongsTo
    {
        return $this->belongsTo(Akta::class);
    }

    public function auditLabel(): string
    {
        return $this->jenis->getLabel().' akta #'.$this->akta_id.' untuk '.$this->penerima;
    }
}
