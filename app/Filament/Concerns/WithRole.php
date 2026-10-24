<?php

namespace App\Filament\Concerns;

use App\Enums\Role;
use App\Models\User;

/** Pintasan peran pengguna untuk resource dan halaman. */
trait WithRole
{
    protected static function user(): ?User
    {
        /** @var User|null */
        return auth()->user();
    }

    protected static function role(): ?Role
    {
        return static::user()?->role;
    }

    protected static function isNotaris(): bool
    {
        return (bool) static::role()?->isNotaris();
    }

    protected static function canWork(): bool
    {
        return (bool) static::role()?->canWork();
    }

    protected static function canViewFiles(): bool
    {
        return (bool) static::role()?->canViewFiles();
    }

    protected static function canFinance(): bool
    {
        return (bool) static::role()?->canFinance();
    }

    protected static function canArchive(): bool
    {
        return (bool) static::role()?->canArchive();
    }

    protected static function canAdminister(): bool
    {
        return (bool) static::role()?->canAdminister();
    }

    /** Tamu situs demo: melihat semua layar, tidak dapat mengubah apa pun. */
    protected static function isTamu(): bool
    {
        return (bool) static::role()?->isTamu();
    }

    /** @return array<int, string> */
    protected static function userOptions(): array
    {
        return User::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all();
    }
}
