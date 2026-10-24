<?php

namespace Tests\Unit;

use App\Services\Ai\Penyamar;
use PHPUnit\Framework\TestCase;

class PenyamarTest extends TestCase
{
    public function test_data_identitas_disamarkan_dan_dikembalikan(): void
    {
        $p = new Penyamar;
        $asli = 'Tuan BUDI SANTOSO, NIK 3273022001800003, telepon 081234567890, email budi@contoh.id, NPWP 01.234.567.8-901.000.';
        $samar = $p->samarkan($asli, ['PIHAK' => ['Budi Santoso']]);

        foreach (['BUDI SANTOSO', '3273022001800003', '081234567890', 'budi@contoh.id', '01.234.567.8-901.000'] as $rahasia) {
            $this->assertStringNotContainsStringIgnoringCase($rahasia, $samar);
        }
        $this->assertSame(5, $p->jumlahToken());
        $this->assertSame(str_ireplace('BUDI SANTOSO', 'Budi Santoso', $asli), $p->kembalikan($samar));
        $this->assertSame(['nama' => 'Budi Santoso'], $p->kembalikanData(['nama' => $p->samarkan('Budi Santoso', ['PIHAK' => ['Budi Santoso']])]));
    }
}
