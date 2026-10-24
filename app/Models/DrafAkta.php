<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrafAkta extends Model
{
    protected $table = 'draf_akta';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['final' => 'boolean', 'temuan' => 'array', 'temuan_ai' => 'array', 'diperiksa_at' => 'datetime'];
    }

    public function akta(): BelongsTo
    {
        return $this->belongsTo(Akta::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TemplateAkta::class, 'template_akta_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jumlahTemuanKritis(): int
    {
        return collect($this->temuan ?? [])->merge($this->temuan_ai ?? [])->where('tingkat', 'kritis')->count();
    }
}
