<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use App\Services\PermissionRegistry;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\PermissionRegistrar;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected array $selectedPermissions = [];

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function afterCreate(): void
    {
        if (!empty($this->selectedPermissions)) {
            $this->record->syncPermissions($this->selectedPermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
