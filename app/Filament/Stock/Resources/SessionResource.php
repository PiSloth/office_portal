<?php

namespace App\Filament\Stock\Resources;

use App\Filament\Stock\Resources\SessionResource\Pages;
use App\Models\CheckSession;
use App\Models\ProductType;
use App\Models\ScanConfig;
use App\Models\User;
use BackedEnum;
use App\Filament\Resources\Concerns\HasPermissionGates;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class SessionResource extends Resource
{
    use HasPermissionGates;

    protected static string $permissionPrefix = 'sessions';

    protected static ?string $model = CheckSession::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static UnitEnum|string|null $navigationGroup = 'Configuration';

    protected static ?string $navigationLabel = 'Sessions';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('sessions.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('sessions.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('sessions.update') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('sessions.delete') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Session Info')->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Select::make('product_type_id')
                    ->label('Product Type')
                    ->options(ProductType::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->reactive(),
                Forms\Components\Select::make('scan_config_id')
                    ->label('Scan Config')
                    ->options(fn(callable $get) => ScanConfig::where('product_type_id', $get('product_type_id'))->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->disabled(fn(callable $get) => ! $get('product_type_id'))
                    ->required(),
                Forms\Components\Select::make('assignedUsers')
                    ->label('Assigned Users')
                    ->relationship('assignedUsers', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'OPEN' => 'Open',
                        'COMPLETED' => 'Completed',
                        'CANCELLED' => 'Cancelled',
                    ])
                    ->default('DRAFT')
                    ->required(),
                Forms\Components\DateTimePicker::make('started_at')
                    ->default(now())
                    ->required(),
                Forms\Components\DateTimePicker::make('completed_at'),
                Forms\Components\Hidden::make('started_by')
                    ->default(fn() => auth()->id()),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'DRAFT' => 'gray',
                        'OPEN' => 'success',
                        'COMPLETED' => 'info',
                        'CANCELLED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('productType.name')
                    ->label('Product Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('scanConfig.name')
                    ->label('Scan Config')
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedUsers.name')
                    ->label('Assigned Users')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList(),
                Tables\Columns\TextColumn::make('startedBy.name')
                    ->label('Started By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'DRAFT' => 'Draft',
                        'OPEN' => 'Open',
                        'COMPLETED' => 'Completed',
                        'CANCELLED' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSessions::route('/'),
            'create' => Pages\CreateSession::route('/create'),
            'edit' => Pages\EditSession::route('/{record}/edit'),
        ];
    }
}
