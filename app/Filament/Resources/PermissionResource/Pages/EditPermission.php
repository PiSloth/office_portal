<?php

namespace App\Filament\Resources\PermissionResource\Pages;

use App\Filament\Resources\PermissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->can('permissions.delete') ?? false),
        ];
    }

    protected function afterSave(): void
    {
        $roles = Role::findMany($this->data['roles'] ?? []);

        $this->record->syncRoles($roles);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        $data['roles'] = $this->record->roles()->pluck('roles.id')->all();

        return $data;
    }
}
