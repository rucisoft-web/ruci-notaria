<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/** Data pengaturan: notaris dan admin TI. */
trait AdminPermissions
{
    use WithRole;

    public static function canViewAny(): bool
    {
        return (bool) static::role()?->canViewSettings();
    }

    public static function canCreate(): bool
    {
        return static::canAdminister();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAdminister();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAdminister();
    }

    public static function canDeleteAny(): bool
    {
        return static::canAdminister();
    }
}
