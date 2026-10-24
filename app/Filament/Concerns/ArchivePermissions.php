<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/** Data protokol: dilihat semua yang boleh melihat berkas; diubah notaris dan arsiparis; tidak dihapus. */
trait ArchivePermissions
{
    use WithRole;

    public static function canViewAny(): bool
    {
        return static::canViewFiles();
    }

    public static function canCreate(): bool
    {
        return static::canArchive();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canArchive();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
