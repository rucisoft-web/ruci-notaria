<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/** Peran pengguna di kantor notaris. */
enum Role: string implements HasColor, HasLabel
{
    use HasOptions;

    case Notaris = 'notaris';
    case Staf = 'staf';
    case Keuangan = 'keuangan';
    case Arsiparis = 'arsiparis';
    case Admin = 'admin';
    // Pengunjung situs demo: melihat semua layar tanpa dapat mengubah data apa pun.
    case Tamu = 'tamu';

    public function getLabel(): string
    {
        return match ($this) {
            self::Notaris => 'Notaris',
            self::Staf => 'Staf / drafter',
            self::Keuangan => 'Keuangan',
            self::Arsiparis => 'Arsiparis',
            self::Admin => 'Admin TI',
            self::Tamu => 'Tamu (hanya lihat)',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Notaris => 'primary',
            self::Staf => 'info',
            self::Keuangan => 'success',
            self::Arsiparis => 'warning',
            self::Admin => 'gray',
            self::Tamu => 'gray',
        };
    }

    /** Melihat dan mengolah berkas, klien, akta (isi rahasia jabatan). */
    public function canWork(): bool
    {
        return in_array($this, [self::Notaris, self::Staf], true);
    }

    /** Melihat isi berkas dan akta tanpa mengubah. */
    public function canViewFiles(): bool
    {
        return $this !== self::Admin;
    }

    /** Memberi nomor akta, menyetujui template, memutus hal yang menjadi tanggung jawab jabatan. */
    public function isNotaris(): bool
    {
        return $this === self::Notaris;
    }

    public function canFinance(): bool
    {
        return in_array($this, [self::Notaris, self::Keuangan], true);
    }

    public function canArchive(): bool
    {
        return in_array($this, [self::Notaris, self::Arsiparis], true);
    }

    /** Pengguna, pengaturan, master data. */
    public function canAdminister(): bool
    {
        return in_array($this, [self::Notaris, self::Admin], true);
    }

    /** Hanya melihat. Semua hak mengubah (canWork, isNotaris, canFinance, canArchive, canAdminister) bernilai false. */
    public function isTamu(): bool
    {
        return $this === self::Tamu;
    }

    /** Melihat seluruh berkas termasuk yang sangat rahasia: notaris, dan tamu situs demo (data fiktif). */
    public function seesAllFiles(): bool
    {
        return in_array($this, [self::Notaris, self::Tamu], true);
    }

    /** Melihat tagihan, pembayaran, dan dana titipan. */
    public function canViewFinance(): bool
    {
        return $this->canFinance() || $this === self::Tamu;
    }

    /** Melihat layar pengaturan dan data induk tanpa mengubahnya. */
    public function canViewSettings(): bool
    {
        return $this->canAdminister() || $this === self::Tamu;
    }
}
