<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Support\Str;

trait HasPermissionGates
{
    public static function canViewAny(): bool
    {
        return auth()->user()?->can(static::permissionName('view')) ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can(static::permissionName('create')) ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can(static::permissionName('update')) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can(static::permissionName('delete')) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can(static::permissionName('delete')) ?? false;
    }

    protected static function permissionName(string $action): string
    {
        return sprintf('%s.%s', static::permissionPrefix(), $action);
    }

    protected static function permissionPrefix(): string
    {
        if (property_exists(static::class, 'permissionPrefix') && is_string(static::$permissionPrefix) && static::$permissionPrefix !== '') {
            return static::$permissionPrefix;
        }

        return static::defaultPermissionPrefix();
    }

    protected static function defaultPermissionPrefix(): string
    {
        return Str::snake(Str::pluralStudly(Str::beforeLast(class_basename(static::class), 'Resource')));
    }
}
