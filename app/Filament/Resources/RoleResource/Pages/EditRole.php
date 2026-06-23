<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('managePermissions')
                ->label('Manage Permissions')
                ->icon('heroicon-o-lock-closed')
                ->modalHeading('Manage Role Permissions')
                ->modalButton('Save Permissions')
                ->form([
                    Forms\Components\Select::make('permissions')
                        ->label('Permissions')
                        ->multiple()
                        ->searchable()
                        ->options(fn (): array => RoleResource::permittedPermissionOptions())
                        ->default(fn (): array => $this->record->permissions()->pluck('name')->all()),
                ])
                ->action(function (Role $record, array $data): void {
                    $record->syncPermissions($data['permissions'] ?? []);
                })
                ->visible(fn (): bool => auth()->user()?->can('roles.update') ?? false),
            Actions\DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->can('roles.delete') ?? false),
        ];
    }
}
