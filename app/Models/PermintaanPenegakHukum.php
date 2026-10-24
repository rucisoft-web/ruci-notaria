<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Pemanggilan notaris atau pengambilan fotokopi minuta untuk proses peradilan (Pasal 66 UUJN). */
class PermintaanPenegakHukum extends Model
{
    use Auditable;

    protected $table = 'permintaan_penegak_hukum';

    protected $guarded = ['id'];

    public const JENIS = ['pemanggilan' => 'Pemanggilan notaris', 'fotokopi_minuta' => 'Pengambilan fotokopi minuta', 'lainnya' => 'Lainnya'];

    protected function casts(): array
    {
        return ['tanggal_surat' => 'date', 'tanggal_persetujuan_mkn' => 'date', 'tanggal_pelaksanaan' => 'date'];
    }

    public function akta(): BelongsTo
    {
        return $this->belongsTo(Akta::class);
    }

    public function auditLabel(): string
    {
        return $this->instansi.' '.$this->nomor_surat;
    }
}
