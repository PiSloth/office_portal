<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $this->record->syncRoles($this->data['roles'] ?? []);
        $this->record->syncPermissions($this->data['permissions'] ?? []);
    }
}
