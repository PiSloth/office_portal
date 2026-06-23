<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SessionResource\Pages;
use App\Models\CheckSession;
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
