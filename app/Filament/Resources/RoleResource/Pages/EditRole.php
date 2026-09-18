<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Services\PermissionRegistry;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected array $selectedPermissions = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $rolePermissions = $this->record->permissions()->pluck('name')->toArray();
        $resources = PermissionRegistry::getResources();
        $allResourcePerms = [];

        foreach ($resources as $key => $res) {
            $prefix = $res['prefix'];
            $actions = array_keys(PermissionRegistry::getActions());
            $matched = [];
            foreach ($actions as $action) {
                $permName = "{$prefix}.{$action}";
                $allResourcePerms[] = $permName;
                if (in_array($permName, $rolePermissions, true)) {
                    $matched[] = $permName;
                }
            }
            $data['permissions_' . $key] = $matched;
        }

        $otherPerms = array_values(array_diff($rolePermissions, $allResourcePerms));
        $data['other_permissions'] = $otherPerms;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $permissions = [];
        foreach (PermissionRegistry::getResources() as $key => $res) {
            if (!empty($data['permissions_' . $key])) {
                $permissions = array_merge($permissions, (array) $data['permissions_' . $key]);
                unset($data['permissions_' . $key]);
            }
        }

        if (!empty($data['other_permissions'])) {
            $permissions = array_merge($permissions, (array) $data['other_permissions']);
            unset($data['other_permissions']);
        }

        $this->selectedPermissions = array_values(array_unique($permissions));

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncPermissions($this->selectedPermissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn(Role $record): bool => $record->name !== 'super-admin' && (auth()->user()?->can('roles.delete') ?? false)),
        ];
    }
}
