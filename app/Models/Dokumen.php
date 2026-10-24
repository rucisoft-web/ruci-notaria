<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Berkas unggahan (KTP, KK, akta badan, sertipikat, dsb). Disimpan privat di storage/app/private. */
class Dokumen extends Model
{
    use Auditable;

    protected $table = 'dokumen';

    protected $guarded = ['id'];

    public const JENIS = [
        'ktp' => 'KTP', 'kk' => 'Kartu Keluarga', 'npwp' => 'NPWP', 'paspor' => 'Paspor', 'kitas' => 'KITAS/KITAP',
        'akta_nikah' => 'Akta/buku nikah', 'akta_badan' => 'Akta pendirian/perubahan badan', 'sk_badan' => 'SK pengesahan badan',
        'sertipikat' => 'Sertipikat tanah', 'pbb' => 'SPPT PBB', 'kuasa' => 'Surat kuasa', 'lainnya' => 'Lainnya',
    ];

    protected function casts(): array
    {
        return ['berlaku_sampai' => 'date'];
    }

    public function dokumenable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function auditLabel(): string
    {
        return $this->judul;
    }
}
