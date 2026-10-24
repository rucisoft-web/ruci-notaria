<?php

namespace App\Services\Ai;

/**
 * Mengganti data identitas dengan token sebelum teks dikirim ke AI, lalu mengembalikannya pada jawaban.
 * Nama dan nilai yang diketahui diganti lebih dulu; pola nomor (NIK, NPWP, rekening, telepon, email) ditangkap regex.
 */
class Penyamar
{
    /** @var array<string, string> token => nilai asli */
    private array $peta = [];

    private array $hitung = [];

    /** @param  array<string, list<string>>  $diketahui  label => daftar nilai, misal ['PIHAK' => ['BUDI SANTOSO']] */
    public function samarkan(string $teks, array $diketahui = []): string
    {
        foreach ($diketahui as $label => $nilai) {
            foreach (array_unique(array_filter($nilai, fn ($v) => mb_strlen(trim((string) $v)) >= 3)) as $v) {
                $token = $this->token($label, $v);
                $teks = preg_replace('/'.preg_quote(trim($v), '/').'/iu', $token, $teks);
            }
        }

        $pola = [
            'EMAIL' => '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
            'NPWP' => '/\b\d{2}\.\d{3}\.\d{3}\.\d-\d{3}\.\d{3}\b/',
            'NIK' => '/\b\d{16}\b/',
            'TELEPON' => '/(\+62|\b0)8\d{8,11}\b/',
            'NOMOR' => '/\b\d{10,}\b/',
        ];
        foreach ($pola as $label => $regex) {
            $teks = preg_replace_callback($regex, fn ($m) => $this->token($label, $m[0]), $teks);
        }

        return $teks;
    }

    public function kembalikan(string $teks): string
    {
        return strtr($teks, $this->peta);
    }

    /** @param  array<mixed>  $data */
    public function kembalikanData(array $data): array
    {
        array_walk_recursive($data, function (&$v) {
            if (is_string($v)) {
                $v = $this->kembalikan($v);
            }
        });

        return $data;
    }

    public function jumlahToken(): int
    {
        return count($this->peta);
    }

    private function token(string $label, string $nilai): string
    {
        $ada = array_search($nilai, $this->peta, true);
        if ($ada !== false) {
            return $ada;
        }
        $this->hitung[$label] = ($this->hitung[$label] ?? 0) + 1;
        $token = '['.$label.'_'.$this->hitung[$label].']';
        $this->peta[$token] = $nilai;

        return $token;
    }
}
