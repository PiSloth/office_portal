<?php

namespace App\Filament\Repurchase\Resources;

use App\Filament\Repurchase\Resources\CalculationParameterResource\Pages;
use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Modules\Core\Calculation\Models\CalculationParameter;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CalculationParameterResource extends Resource
{
    use HasPermissionGates;
    protected static string $permissionPrefix = 'calculation-parameters';
    protected static ?string $model = CalculationParameter::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-adjustments-vertical';
    protected static \UnitEnum|string|null $navigationGroup = 'Configuration';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\Select::make('method_id')
                    ->relationship('method', 'name')
                    ->required()
                    ->disabled(fn($record) => $record !== null),
                Forms\Components\TextInput::make('key')
                    ->required()
                    ->maxLength(255)
                    ->disabled(fn($record) => $record !== null),
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('type')
                    ->options([
                        'numeric' => 'Numeric',
                        'string' => 'String',
                        'boolean' => 'Boolean',
                        'json' => 'JSON',
                    ])
                    ->required()
                    ->disabled(fn($record) => $record !== null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('method.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('key')
                    ->searchable(),
                Tables\Columns\TextColumn::make('value')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Modified'),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculationParameters::route('/'),
            'create' => Pages\CreateCalculationParameter::route('/create'),
            'edit' => Pages\EditCalculationParameter::route('/{record}/edit'),
        ];
    }
}
