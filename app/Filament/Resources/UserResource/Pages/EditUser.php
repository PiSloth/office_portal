<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function afterSave(): void
    {
        $roles = Role::findMany($this->data['roles'] ?? []);

        $this->record->syncRoles($roles);
        $this->record->syncPermissions($this->data['permissions'] ?? []);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        $data['roles'] = $this->record->roles()->pluck('roles.id')->all();
        $data['permissions'] = $this->record->permissions()->pluck('permissions.name')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $currentUser = auth()->user();
        $assignedRoles = collect($data['roles'] ?? [])->map(fn ($roleId) => Role::find($roleId))->filter()->all();

        if ($currentUser && ! $currentUser->hasRole('super-admin')) {
            $currentUserPermissions = $currentUser->getAllPermissions()->pluck('name')->all();
            $assignedRoles = collect($assignedRoles)->filter(function (?Role $role): bool {
                if (! $role) {
                    return false;
                }

                $extra = $role->permissions->pluck('name')->diff($currentUserPermissions);
                return $extra->isEmpty();
            })->pluck('id')->all();

            $data['roles'] = $assignedRoles;
            $data['permissions'] = collect($data['permissions'] ?? [])->filter(fn (string $permission): bool => in_array($permission, $currentUserPermissions, true))->all();
        }

        return $data;
    }
}
