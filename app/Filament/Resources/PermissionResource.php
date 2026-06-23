<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use BackedEnum;
use App\Filament\Resources\Concerns\HasPermissionGates;
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

class PermissionResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Permission::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-key';

    protected static UnitEnum|string|null $navigationGroup = 'User Management';

    protected static ?string $navigationLabel = 'Permissions';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('permissions.view') || auth()->user()?->can('permissions.manage') || false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('permissions.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('permissions.update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('permissions.delete') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Permission Details')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Use dot notation, e.g. users.view')
                    ->maxLength(255),
                Forms\Components\TextInput::make('guard_name')
                    ->default('web')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->options(fn (): array => self::availableRoleOptions())
                    ->dehydrated(false)
                    ->helperText('Assign this permission to one or more roles.'),
            ])->columns(1)->columnSpan('full'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('guard_name')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('permissions.update') ?? false),
                Actions\DeleteAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('permissions.delete') ?? false),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }

    public static function availableRoleOptions(): array
    {
        return Role::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
