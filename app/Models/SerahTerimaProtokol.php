<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

/** Serah terima protokol notaris (Pasal 62 sampai 65 UUJN). */
class SerahTerimaProtokol extends Model
{
    use Auditable;

    protected $table = 'serah_terima_protokol';

    protected $guarded = ['id'];

    public const ALASAN = [
        'meninggal' => 'Notaris meninggal dunia', 'masa_jabatan' => 'Masa jabatan berakhir', 'permintaan' => 'Permintaan sendiri',
        'tidak_mampu' => 'Tidak mampu secara rohani/jasmani', 'pejabat_negara' => 'Diangkat menjadi pejabat negara', 'pindah' => 'Pindah wilayah jabatan',
        'berhenti_sementara' => 'Diberhentikan sementara', 'berhenti_tidak_hormat' => 'Diberhentikan dengan tidak hormat', 'cuti' => 'Cuti (kepada notaris pengganti)',
    ];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'ada_protokol_25_tahun' => 'boolean'];
    }

    public function auditLabel(): string
    {
        return 'Serah terima '.$this->arah.' '.$this->notaris_asal.' → '.$this->notaris_penerima;
    }
}
