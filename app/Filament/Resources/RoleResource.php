<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use BackedEnum;
use App\Filament\Resources\Concerns\HasPermissionGates;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use App\Services\PermissionRegistry;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use UnitEnum;

class RoleResource extends Resource
{
    use HasPermissionGates;

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
            && (
                (auth()->user()?->can('roles.update') ?? false)
                || (auth()->user()?->can('roles.assign') ?? false)
            );
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
            ]),

            \Filament\Schemas\Components\Tabs::make('Role Configuration')
                ->tabs([
                    \Filament\Schemas\Components\Tabs\Tab::make('Resource Permissions')
                        ->icon('heroicon-o-shield-check')
                        ->schema(self::getResourceSections()),

                    \Filament\Schemas\Components\Tabs\Tab::make('Custom Permissions')
                        ->icon('heroicon-o-key')
                        ->schema([
                            Section::make('System & Custom Permissions')
                                ->description('Non-resource permissions such as system config, report views, and special operations.')
                                ->schema([
                                    Forms\Components\CheckboxList::make('other_permissions')
                                        ->hiddenLabel()
                                        ->bulkToggleable()
                                        ->columns(4)
                                        ->options(fn (): array => self::getOtherPermissionOptions())
                                        ->dehydrated(true),
                                ]),
                        ]),

                    \Filament\Schemas\Components\Tabs\Tab::make('Role Manual')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Section::make('How Roles and Permissions Work')->schema([
                                Forms\Components\Placeholder::make('role_permission_manual')
                                    ->hiddenLabel()
                                    ->content(new HtmlString(
                                        '<div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">'
                                            . '<p>Roles are groups of permissions. Assigning a role to a user grants that user all permissions attached to the role.</p>'
                                            . '<p>Permissions are created separately and can be assigned only if the signed-in user already owns them.</p>'
                                            . '<ul class="list-disc list-inside space-y-2">'
                                            . '<li><strong>Role creation:</strong> Enter a unique role name, then select permissions from the list.</li>'
                                            . '<li><strong>Permission limitation:</strong> You can only assign permissions you already have yourself.</li>'
                                            . '<li><strong>Super Admin:</strong> The <code>super-admin</code> role cannot be edited or deleted.</li>'
                                            . '<li><strong>Role name uniqueness:</strong> Role names must be unique across the system.</li>'
                                            . '<li><strong>Managing permissions:</strong> Permissions can be toggled per resource using the Select all option.</li>'
                                            . '</ul>'
                                            . '<p class="text-xs text-gray-500 dark:text-gray-400">Note: Higher-level permissions cannot be assigned unless you already have them.</p>'
                                            . '</div>'
                                    )),
                            ]),
                        ]),
                ])
                ->columnSpanFull(),
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
                    ->visible(fn(Role $record): bool => $record->name !== 'super-admin' && ((auth()->user()?->can('roles.update') ?? false) || (auth()->user()?->can('roles.assign') ?? false))),
                Actions\Action::make('delete')
                    ->label('Delete')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn(Role $record) => $record->delete())
                    ->visible(fn(Role $record): bool => $record->name !== 'super-admin' && (auth()->user()?->can('roles.delete') ?? false)),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->visible(fn(): bool => auth()->user()?->can('roles.delete') ?? false),
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

    public static function getResourceSections(): array
    {
        $resources = PermissionRegistry::getResources();
        $actions = PermissionRegistry::getActions();
        $sections = [];

        foreach ($resources as $key => $res) {
            $options = [];
            foreach ($actions as $actionKey => $actionLabel) {
                $options["{$res['prefix']}.{$actionKey}"] = $actionLabel;
            }

            $sections[] = Section::make($res['label'])
                ->description($res['model'])
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\CheckboxList::make('permissions_' . $key)
                        ->hiddenLabel()
                        ->bulkToggleable()
                        ->columns(4)
                        ->options($options)
                        ->dehydrated(true),
                ]);
        }

        return $sections;
    }

    public static function getOtherPermissionOptions(): array
    {
        $allResourcePerms = PermissionRegistry::getAllResourcePermissionNames();

        return Permission::query()
            ->whereNotIn('name', $allResourcePerms)
            ->orderBy('name')
            ->pluck('name', 'name')
            ->all();
    }

    public static function permittedPermissionOptions(): array
    {
        $user = auth()->user();

        if ($user?->can('roles.assign') || $user?->can('permissions.manage')) {
            return Permission::query()
                ->orderBy('name')
                ->pluck('name', 'id')
                ->all();
        }

        $own = $user?->getAllPermissions()->pluck('id')->all() ?? [];

        return Permission::query()
            ->whereIn('id', $own)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
