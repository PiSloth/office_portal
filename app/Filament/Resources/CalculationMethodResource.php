<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalculationMethodResource\Pages;
use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Modules\Core\Calculation\Models\CalculationMethod;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CalculationMethodResource extends Resource
{
    use HasPermissionGates;
    protected static string $permissionPrefix = 'calculation-methods';
    protected static ?string $model = CalculationMethod::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';
    protected static \UnitEnum|string|null $navigationGroup = 'Configuration';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\TextInput::make('php_class_name')->required()->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('php_class_name')->searchable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListCalculationMethods::route('/'),
            'create' => Pages\CreateCalculationMethod::route('/create'),
            'edit' => Pages\EditCalculationMethod::route('/{record}/edit'),
        ];
    }
}
