<?php

namespace App\Filament\Repurchase\Resources;

use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Filament\Repurchase\Resources\WorkflowResource\Pages;
use App\Modules\Core\Workflow\Models\Workflow;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkflowResource extends Resource
{
    use HasPermissionGates;
    protected static string $permissionPrefix = 'workflows';
    protected static ?string $model = Workflow::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-rectangle-group';
    protected static \UnitEnum|string|null $navigationGroup = 'Configuration';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Select::make('product_type_id')
                    ->relationship('productType', 'name')
                    ->nullable()
                    ->helperText('Select a product type to associate with this workflow.'),
                Forms\Components\Textarea::make('description')->maxLength(65535)->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('productType.name')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
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
            WorkflowResource\RelationManagers\StatesRelationManager::class,
            WorkflowResource\RelationManagers\TransitionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflows::route('/'),
            'create' => Pages\CreateWorkflow::route('/create'),
            'edit' => Pages\EditWorkflow::route('/{record}/edit'),
        ];
    }
}
