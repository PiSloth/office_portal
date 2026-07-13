<?php

namespace App\Filament\Stock\Resources;

use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Filament\Stock\Resources\DecisionRuleResource\Pages;
use App\Models\DecisionRule;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class DecisionRuleResource extends Resource
{
    use HasPermissionGates;

    protected static string $permissionPrefix = 'decisions';

    protected static ?string $model = DecisionRule::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-funnel';

    protected static UnitEnum|string|null $navigationGroup = 'Decision Management';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('criteria_field')
                    ->required()
                    ->helperText('Example: weight_g, location_id, code')
                    ->maxLength(255),
                Forms\Components\Select::make('criteria_condition')
                    ->required()
                    ->options([
                        'mismatch' => 'Mismatch',
                        'exceeds_tolerance' => 'Exceeds Tolerance',
                        'greater_than' => 'Greater Than (Actual > Expected)',
                        'less_than' => 'Less Than (Actual < Expected)',
                    ]),
                Forms\Components\Select::make('decision_type_id')
                    ->relationship('decisionType', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('criteria_field')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('criteria_condition')->badge()->sortable(),
            Tables\Columns\TextColumn::make('decisionType.name')->label('Decision Type')->sortable(),
            Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
        ])->actions([
            Actions\EditAction::make(),
        ])->bulkActions([
            Actions\BulkActionGroup::make([
                Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDecisionRules::route('/'),
            'create' => Pages\CreateDecisionRule::route('/create'),
            'edit' => Pages\EditDecisionRule::route('/{record}/edit'),
        ];
    }
}
