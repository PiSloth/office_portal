<?php

namespace App\Filament\Repurchase\Resources;

use App\Filament\Repurchase\Resources\ValidationRuleSetResource\Pages;
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
                Forms\Components\Toggle::make('is_push_decision')
                    ->label('Push to Decision on Fail')
                    ->helperText('If a check fails, create a purchase decision resource with status as open.')
                    ->default(false),

                \Filament\Schemas\Components\Section::make('Rules')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(12)
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('header_label')
                                    ->hiddenLabel()
                                    ->content('Rule Label')
                                    ->columnSpan(3),
                                \Filament\Forms\Components\Placeholder::make('header_field')
                                    ->hiddenLabel()
                                    ->content('Field Name')
                                    ->columnSpan(3),
                                \Filament\Forms\Components\Placeholder::make('header_operator')
                                    ->hiddenLabel()
                                    ->content('Operator')
                                    ->columnSpan(2),
                                \Filament\Forms\Components\Placeholder::make('header_expected')
                                    ->hiddenLabel()
                                    ->content('Expected Source')
                                    ->columnSpan(3),
                                \Filament\Forms\Components\Placeholder::make('header_tolerance')
                                    ->hiddenLabel()
                                    ->content('Tolerance')
                                    ->columnSpan(1),
                            ])
                            ->extraAttributes(['class' => 'font-semibold border-b pb-2 mb-2 hidden md:grid']),

                        Forms\Components\Repeater::make('rules')
                            ->relationship()
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->placeholder('e.g. Check Gold Grade')
                                    ->maxLength(255)
                                    ->live(debounce: 500)
                                    ->hiddenLabel()
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'A user-friendly label describing what this rule checks.')
                                    ->columnSpan(3),
                                Forms\Components\Select::make('field_name')
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->options(function (callable $get) {
                                        $productTypeId = self::getProductTypeIdFromState($get);
                                        return self::getFieldOptions($productTypeId);
                                    })
                                    ->hiddenLabel()
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'The system key of the field from the purchase request to be validated.')
                                    ->columnSpan(3),
                                Forms\Components\Select::make('operator')
                                    ->required()
                                    ->options([
                                        'equals' => 'Equals',
                                        'tolerance' => 'Tolerance',
                                        'greater_than' => 'Greater Than',
                                        'less_than' => 'Less Than',
                                    ])
                                    ->default('equals')
                                    ->live()
                                    ->hiddenLabel()
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Comparison method to use when matching actual value against expected value.')
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('expected_source')
                                    ->placeholder('Expected value / field source')
                                    ->hiddenLabel()
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'The expected target value, or another field name key to extract value dynamically.')
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('tolerance')
                                    ->numeric()
                                    ->placeholder('Tolerance')
                                    ->visible(fn(callable $get) => $get('operator') === 'tolerance')
                                    ->hiddenLabel()
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Allowed numeric offset or variance (e.g. 0.05).')
                                    ->columnSpan(1),
                                Forms\Components\Toggle::make('is_required')
                                    ->label('Required')
                                    ->inline(false)
                                    ->default(false)
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'If active, the system automatically checks input against expected value. If inactive, verifier manually marks Pass/Fail.')
                                    ->columnSpan(2),
                                Forms\Components\Toggle::make('is_editable')
                                    ->label('Editable')
                                    ->inline(false)
                                    ->default(true)
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Controls whether the verifier is allowed to modify the checked value field during validation.')
                                    ->columnSpan(2),
                                Forms\Components\Toggle::make('is_based_grade')
                                    ->label('Based Grade')
                                    ->inline(false)
                                    ->live()
                                    ->default(false)
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Filters this rule so that it only runs for items matching specific gold grades.')
                                    ->columnSpan(2),
                                Forms\Components\Select::make('grades_json')
                                    ->label('Grades')
                                    ->multiple()
                                    ->options([
                                        '16' => '16 ပဲ',
                                        '15' => '15 ပဲ',
                                        '14.2' => 'ဒင်္ဂါး (14.2)',
                                        '14' => '14 ပဲ',
                                        '13' => '13 ပဲ',
                                        '12' => '12 ပဲ',
                                        '10' => '10 ပဲ',
                                        '8' => '8 ပဲ',
                                        '4' => '4 ပဲ',
                                    ])
                                    ->visible(fn(callable $get) => $get('is_based_grade') === true)
                                    ->required(fn(callable $get) => $get('is_based_grade') === true)
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'List of gold grades this rule applies to.')
                                    ->columnSpan(4),
                                Forms\Components\Toggle::make('is_skip_zero')
                                    ->label('Skip 0')
                                    ->inline(false)
                                    ->default(false)
                                    ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'If active, this rule is completely skipped if the expected value resolves to 0.')
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
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
            'quantity' => 'Quantity',
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
                'is_good' => 'ရ/မရ',
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
                Tables\Columns\IconColumn::make('is_push_decision')
                    ->label('Push to Decision')
                    ->boolean()
                    ->sortable(),
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
