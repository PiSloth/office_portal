<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ValidationRuleSetResource\Pages;
use App\Modules\Core\Validation\Models\ValidationRuleSet;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ValidationRuleSetResource extends Resource
{
    use \App\Filament\Resources\Concerns\HasPermissionGates;
    protected static string $permissionPrefix = 'validation-rule-sets';
    protected static ?string $model = ValidationRuleSet::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-check-badge';
    protected static \UnitEnum|string|null $navigationGroup = 'Configuration';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')->required()->maxLength(255),
                Forms\Components\Textarea::make('description')->maxLength(65535)->columnSpanFull(),
                Forms\Components\Select::make('product_type_id')
                    ->relationship('productType', 'name')
                    ->live()
                    ->required()
                    ->helperText('Associate this rule set with a specific product type.'),

                \Filament\Schemas\Components\Section::make('Rules')
                    ->schema([
                        Forms\Components\Repeater::make('rules')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('Rule Label')
                                    ->placeholder('e.g. Check Gold Grade')
                                    ->maxLength(255)
                                    ->live(debounce: 500)
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('field_name')
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->options(function (callable $get) {
                                        $productTypeId = self::getProductTypeIdFromState($get);
                                        return self::getFieldOptions($productTypeId);
                                    }),
                                Forms\Components\Select::make('operator')
                                    ->required()
                                    ->options([
                                        'equals' => 'Equals',
                                        'tolerance' => 'Tolerance',
                                        'greater_than' => 'Greater Than',
                                        'less_than' => 'Less Than',
                                    ])
                                    ->default('equals')
                                    ->live(),
                                Forms\Components\TextInput::make('expected_source')
                                    ->label('Expected Value / Source')
                                    ->helperText('Hardcoded expected value or dynamic field source.'),
                                Forms\Components\TextInput::make('tolerance')
                                    ->numeric()
                                    ->label('Tolerance')
                                    ->visible(fn(callable $get) => $get('operator') === 'tolerance'),
                                Forms\Components\Toggle::make('is_required')
                                    ->default(false),
                                Forms\Components\Toggle::make('is_editable')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->itemLabel(fn(array $state): ?string => ($state['label'] ?? $state['field_name'] ?? 'New Rule') . ' (' . ($state['operator'] ?? '') . ')'),
                    ])
            ]);
    }

    private static function getProductTypeIdFromState(callable $get): ?string
    {
        foreach (
            [
                'product_type_id',
                '../product_type_id',
                '../../product_type_id',
                '../../../product_type_id',
                '../../../../product_type_id',
            ] as $path
        ) {
            $productTypeId = $get($path);

            if (filled($productTypeId)) {
                return (string) $productTypeId;
            }
        }

        return null;
    }

    public static function getFieldOptions(?string $productTypeId): array
    {
        $standardFields = [
            'branch_id' => 'Branch ID',
            'customer_name' => 'Customer Name',
            'customer_phone' => 'Customer Phone',
            'total_amount' => 'Total Amount',
        ];

        if (! $productTypeId) {
            return $standardFields;
        }

        $dynamicFields = \App\Models\ProductTypeField::query()
            ->where('product_type_id', $productTypeId)
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function (\App\Models\ProductTypeField $field): array {
                $label = $field->field_label;
                if ($field->field_type) {
                    $label .= ' (' . $field->field_type . ')';
                }

                return [$field->field_name => $label];
            })
            ->all();

        // Dynamically append calculator fields if the product type is Jewelry
        $productType = \App\Models\ProductType::find($productTypeId);
        if ($productType && ($productType->code === 'JEWELRY' || strtolower($productType->name) === 'jewelry')) {
            $jewelryCalculatorFields = [
                'product_name' => 'Product Name',
                'goldList' => 'Gold Grade / Grade of Gold',
                'kyat' => 'Kyat (ကျပ်)',
                'pae' => 'Pae (ပဲ)',
                'yawe' => 'Yawe (ရွေး)',
                'kyaukWeight' => 'Kyauk Weight (ကျောက်ချိန် ရွေး)',
                'goldWeightGram' => 'Gold Weight Gram (g)',
                'percent' => 'Percent Deduction (%)',
                'reChange' => 'Re-change (အလဲအထပ်)',
            ];
            $dynamicFields = array_merge($dynamicFields, $jewelryCalculatorFields);
        }

        return array_merge($standardFields, $dynamicFields);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('productType.name')->searchable()->sortable(),
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
            'index' => Pages\ListValidationRuleSets::route('/'),
            'create' => Pages\CreateValidationRuleSet::route('/create'),
            'edit' => Pages\EditValidationRuleSet::route('/{record}/edit'),
        ];
    }
}
