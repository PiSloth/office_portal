<?php

namespace App\Filament\Resources\Concerns;

use Illuminate\Support\Str;

trait HasPermissionGates
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->can(static::permissionName('view_any')) || $user?->can(static::permissionName('view'))) ?? false;
    }

    public static function canView($record): bool
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

    public static function canRestore($record): bool
    {
        return auth()->user()?->can(static::permissionName('restore')) ?? false;
    }

    public static function canRestoreAny(): bool
    {
        $user = auth()->user();

        return ($user?->can(static::permissionName('restore_any')) || $user?->can(static::permissionName('restore'))) ?? false;
    }

    public static function canReplicate($record): bool
    {
        return auth()->user()?->can(static::permissionName('replicate')) ?? false;
    }

    public static function canReorder(): bool
    {
        return auth()->user()?->can(static::permissionName('reorder')) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can(static::permissionName('delete')) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        $user = auth()->user();

        return ($user?->can(static::permissionName('delete_any')) || $user?->can(static::permissionName('delete'))) ?? false;
    }

    public static function canForceDelete($record): bool
    {
        return auth()->user()?->can(static::permissionName('force_delete')) ?? false;
    }

    public static function canForceDeleteAny(): bool
    {
        $user = auth()->user();

        return ($user?->can(static::permissionName('force_delete_any')) || $user?->can(static::permissionName('force_delete'))) ?? false;
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
