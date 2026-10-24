<?php

namespace App\Models;

use App\Enums\StatusTemplate;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Template .docx dengan variabel ${...}. Hanya versi berstatus disetujui yang dapat dipakai membuat draf. */
class TemplateAkta extends Model
{
    use Auditable;

    protected $table = 'template_akta';

    protected $guarded = ['id'];

    public array $tracked = ['status', 'versi'];

    protected function casts(): array
    {
        return ['status' => StatusTemplate::class, 'disetujui_at' => 'datetime'];
    }

    public function jenisLayanan(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class);
    }

    public function disetujuiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disetujui_oleh');
    }

    public function auditLabel(): string
    {
        return $this->nama.' v'.$this->versi;
    }
}
