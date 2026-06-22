<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use UnitEnum;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-check';

    protected static UnitEnum|string|null $navigationGroup = 'User Management';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('roles.view') || auth()->user()?->can('roles.assign') || false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('roles.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return $record instanceof Role
            && $record->name !== 'super-admin'
            && (auth()->user()?->can('roles.update') ?? false);
    }

    public static function canDelete($record): bool
    {
        return $record instanceof Role
            && $record->name !== 'super-admin'
            && (auth()->user()?->can('roles.delete') ?? false);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Role Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Select::make('permissions')
                    ->label('Permissions')
                    ->multiple()
                    ->searchable()
                    ->options(fn (): array => self::permittedPermissionOptions())
                    ->helperText('Only permissions you already own can be assigned.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('permissions.name')->badge()->label('Permissions')->limit(4),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->visible(fn (Role $record): bool => $record->name !== 'super-admin' && (auth()->user()?->can('roles.update') ?? false)),
                Actions\Action::make('delete')
                    ->label('Delete')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Role $record) => $record->delete())
                    ->visible(fn (Role $record): bool => $record->name !== 'super-admin' && (auth()->user()?->can('roles.delete') ?? false)),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->can('roles.delete') ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }

    protected static function permittedPermissionOptions(): array
    {
        $user = auth()->user();
        $own = $user?->getAllPermissions()->pluck('name')->all() ?? [];

        return Permission::query()
            ->whereIn('name', $own)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();
    }
}
