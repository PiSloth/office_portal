<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $roles = Role::findMany($this->data['roles'] ?? []);

        $this->record->syncRoles($roles);
        $this->record->syncPermissions($this->data['permissions'] ?? []);
    }
}
