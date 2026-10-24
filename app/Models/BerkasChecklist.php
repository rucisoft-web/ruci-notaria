<?php

namespace App\Models;

use App\Enums\StatusChecklist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BerkasChecklist extends Model
{
    protected $table = 'berkas_checklist';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => StatusChecklist::class];
    }

    public function berkas(): BelongsTo
    {
        return $this->belongsTo(Berkas::class);
    }

    public function dokumen(): BelongsTo
    {
        return $this->belongsTo(Dokumen::class);
    }
}
