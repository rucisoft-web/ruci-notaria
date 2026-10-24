<?php

namespace App\Services\Ai;

use App\Models\AiPermintaan;
use App\Models\Berkas;
use App\Models\Pengaturan;
use App\Models\Subjek;

/**
 * Semua syarat sebelum data dikirim ke penyedia AI (bagian 5.3 spesifikasi).
 * AI nonaktif bawaan; notaris mengaktifkan per fitur di halaman Pengaturan.
 */
class AiGerbang
{
    /** @return string|null alasan penolakan, atau null bila boleh. */
    public function alasanTolak(string $fitur, ?Berkas $berkas = null, ?Subjek $subjek = null): ?string
    {
        return match (true) {
            (bool) auth()->user()?->isTamu() => 'Fitur AI tidak tersedia untuk akun tamu.',
            ! Pengaturan::get('ai.aktif') => 'Fitur AI belum diaktifkan notaris (Pengaturan › AI).',
            ! Pengaturan::get("ai.{$fitur}") => 'Fitur AI ini belum diaktifkan notaris.',
            blank(config('notaria.ai.api_key')) => 'OPENAI_API_KEY belum diisi di file .env.',
            AiPermintaan::biayaBulanIni() >= (float) Pengaturan::get('ai.batas_bulanan_usd', 20) => 'Batas biaya AI bulan ini sudah tercapai.',
            $berkas?->sangat_rahasia === true => 'Berkas bertanda sangat rahasia tidak dikirim ke AI.',
            $subjek !== null && $subjek->exists && ! $subjek->persetujuan_ai => 'Klien belum memberi persetujuan pemrosesan data oleh AI.',
            default => null,
        };
    }

    public function boleh(string $fitur, ?Berkas $berkas = null, ?Subjek $subjek = null): bool
    {
        return $this->alasanTolak($fitur, $berkas, $subjek) === null;
    }
}
