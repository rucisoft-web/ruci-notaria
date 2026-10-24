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

    public function getLabel(): string
    {
        return match ($this) {
            self::Notaris => 'Notaris',
            self::Staf => 'Staf / drafter',
            self::Keuangan => 'Keuangan',
            self::Arsiparis => 'Arsiparis',
            self::Admin => 'Admin TI',
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
}
