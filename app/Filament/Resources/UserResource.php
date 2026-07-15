<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\PasswordResetLog;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use App\Filament\Resources\Concerns\HasPermissionGates;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use UnitEnum;

class UserResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = User::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-users';
    
    protected static UnitEnum|string|null $navigationGroup = 'User Management';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('users.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('users.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('users.update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('users.delete') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('User Setup')
                ->tabs([
                    Tab::make('Basic Info')
                        ->schema([
                            Section::make()->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('password')
                                    ->password()
                                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->required(fn (string $context): bool => $context === 'create')
                                    ->maxLength(255),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'ACTIVE' => 'Active',
                                        'SUSPENDED' => 'Suspended',
                                    ])
                                    ->default('ACTIVE')
                                    ->required(),
                                Forms\Components\Select::make('branch_id')
                                    ->relationship('branch', 'name')
                                    ->label('Branch')
                                    ->placeholder('Select Branch'),
                            ])->columns(2),
                        ]),
                    Tab::make('Roles')
                        ->schema([
                            Section::make('Assign Roles')->schema([
                                    Forms\Components\Select::make('roles')
                                        ->label('Roles')
                                        ->multiple()
                                        ->searchable()
                                        ->preload()
                                        ->options(fn (): array => self::permittedRoleOptions())
                                        ->helperText('You can only assign roles that do not grant permissions beyond your own authority.')
                                        ->required(),
                            ]),
                        ]),
                    Tab::make('Permissions')
                        ->schema([
                            Section::make('Direct Permissions')->schema([
                                Forms\Components\Select::make('permissions')
                                    ->label('Extra Permissions')
                                    ->multiple()
                                    ->searchable()
                                    ->options(fn (): array => self::permittedPermissionOptions())
                                    ->helperText('Direct permissions are added on top of role permissions.'),
                            ]),
                        ]),
                ])
                ->persistTabInQueryString(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('roles.name')->badge()->label('Roles')->limit(3),
                Tables\Columns\TextColumn::make('permissions.name')->badge()->label('Direct Permissions')->limit(3),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ACTIVE' => 'success',
                        'SUSPENDED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ACTIVE' => 'Active',
                        'SUSPENDED' => 'Suspended',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make()
                    ->visible(fn (): bool => auth()->user()?->can('users.update') ?? false),
                Actions\Action::make('suspend')
                    ->action(fn (User $record) => $record->update(['status' => 'SUSPENDED']))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-lock-closed')
                    ->visible(fn (User $record) => ($record->status === 'ACTIVE') && auth()->id() !== $record->id && (auth()->user()?->can('users.update') ?? false)),
                Actions\Action::make('activate')
                    ->action(fn (User $record) => $record->update(['status' => 'ACTIVE']))
                    ->requiresConfirmation()
                    ->color('success')
                    ->icon('heroicon-o-lock-open')
                    ->visible(fn (User $record) => $record->status === 'SUSPENDED' && (auth()->user()?->can('users.update') ?? false)),
                Actions\Action::make('resetPassword')
                    ->form([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required()
                            ->minLength(8)
                            ->maxLength(255),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update([
                            'password' => Hash::make($data['password']),
                        ]);
                        PasswordResetLog::create([
                            'user_id' => $record->id,
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'created_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->color('warning')
                    ->icon('heroicon-o-key')
                    ->visible(fn (): bool => auth()->user()?->can('users.update') ?? false),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export to Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $records->load(['branch', 'roles']);
                            return new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($records) {
                                $handle = fopen('php://output', 'w');
                                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
                                fputcsv($handle, ['User name', 'Email', 'Branch', 'Role']);

                                foreach ($records as $record) {
                                    fputcsv($handle, [
                                        $record->name,
                                        $record->email,
                                        $record->branch?->name ?? 'N/A',
                                        $record->roles->pluck('name')->implode(', '),
                                    ]);
                                }

                                fclose($handle);
                            }, 200, [
                                'Content-Type' => 'text/csv; charset=UTF-8',
                                'Content-Disposition' => 'attachment; filename="users_export_' . now()->format('Y-m-d_H-i-s') . '.csv"',
                            ]);
                        }),
                    Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => auth()->user()?->can('users.delete') ?? false),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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

    protected static function permittedRoleOptions(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $ownPermissions = $user->getAllPermissions()->pluck('name')->all();

        return Role::query()
            ->when(! $user->hasRole('super-admin'), fn ($query) => $query->whereDoesntHave('permissions', fn ($query) => $query->whereNotIn('name', $ownPermissions)))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }
}
