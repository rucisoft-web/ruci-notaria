<?php

namespace App\Filament\Resources\AiPermintaan\Pages;

use App\Filament\Resources\AiPermintaan\AiPermintaanResource;
use App\Models\AiPermintaan;
use App\Models\Pengaturan;
use Filament\Resources\Pages\ListRecords;

class ListAiPermintaan extends ListRecords
{
    protected static string $resource = AiPermintaanResource::class;

    public function getSubheading(): ?string
    {
        return sprintf('Biaya bulan ini: USD %s dari batas USD %s.', number_format(AiPermintaan::biayaBulanIni(), 2), number_format((float) Pengaturan::get('ai.batas_bulanan_usd'), 2));
    }
}
