<?php

namespace App\Filament\Stock\Resources;

use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Filament\Stock\Resources\ScanConfigResource\Pages;
use App\Models\ScanConfig;
use App\Models\ProductTypeField;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class ScanConfigResource extends Resource
{
    use HasPermissionGates;

    protected static string $permissionPrefix = 'scan-configs';

    protected static ?string $model = ScanConfig::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static UnitEnum|string|null $navigationGroup = 'Configuration';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                        Forms\Components\Select::make('product_type_id')
                            ->relationship('productType', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn($state, Set $set) => $set('config_json.fields', [])),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Scan Form Fields Configuration')
                    ->description('Define the fields to scan and how they should be verified.')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(12)
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('header_field_name')
                                    ->hiddenLabel()
                                    ->content('Field Name')
                                    ->columnSpan(2),
                                \Filament\Forms\Components\Placeholder::make('header_field')
                                    ->hiddenLabel()
                                    ->content('Product Field')
                                    ->columnSpan(2),
                                \Filament\Forms\Components\Placeholder::make('header_source')
                                    ->hiddenLabel()
                                    ->content('Source')
                                    ->columnSpan(1),
                                \Filament\Forms\Components\Placeholder::make('header_required')
                                    ->hiddenLabel()
                                    ->content('Required')
                                    ->columnSpan(1),
                                \Filament\Forms\Components\Placeholder::make('header_compare')
                                    ->hiddenLabel()
                                    ->content('Compare')
                                    ->columnSpan(1),
                                \Filament\Forms\Components\Placeholder::make('header_tolerance')
                                    ->hiddenLabel()
                                    ->content('Tolerance')
                                    ->columnSpan(1),
                                \Filament\Forms\Components\Placeholder::make('header_is_editable')
                                    ->hiddenLabel()
                                    ->content('Editable')
                                    ->columnSpan(1),
                                \Filament\Forms\Components\Placeholder::make('header_is_quickcheck')
                                    ->hiddenLabel()
                                    ->content('Quick Check')
                                    ->columnSpan(1),
                                \Filament\Forms\Components\Placeholder::make('header_is_apply_validate')
                                    ->hiddenLabel()
                                    ->content('Apply Rule')
                                    ->columnSpan(2),
                            ])
                            ->extraAttributes(['class' => 'font-semibold border-b pb-2 mb-2 hidden md:grid', 'style' => 'min-width: 1200px;']),

                        Forms\Components\Repeater::make('config_json.fields')
                            ->label('Fields Checklist')
                            ->extraAttributes(['style' => 'overflow-x-auto;'])
                            ->schema([
                                Grid::make(12)
                                    ->extraAttributes(['style' => 'min-width: 1200px;'])
                                    ->schema([
                                        Forms\Components\TextInput::make('field_name')
                                            ->placeholder('e.g. Weight')
                                            ->required()
                                            ->maxLength(255)
                                            ->hiddenLabel()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'A user-friendly label describing what this field is.')
                                            ->columnSpan(2),

                                        Forms\Components\Select::make('field')
                                            ->required()
                                            ->searchable()
                                            ->live()
                                            ->options(function (callable $get) {
                                                $productTypeId = self::getProductTypeIdFromState($get);
                                                return self::getFieldOptions($productTypeId);
                                            })
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if (blank($get('field_name')) && filled($state)) {
                                                    $set('field_name', self::resolveFieldLabel((string) $state, self::getProductTypeIdFromState($get)));
                                                }
                                            })
                                            ->hiddenLabel()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'The database key of the field from the product data.')
                                            ->columnSpan(2),

                                        Forms\Components\Select::make('source')
                                            ->required()
                                            ->options([
                                                'product' => 'Expected (from Product Master Data)',
                                                'check' => 'Actual Input Only (e.g. checker notes)',
                                            ])
                                            ->default('product')
                                            ->hiddenLabel()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Source of the expected target value.')
                                            ->columnSpan(1),

                                        Forms\Components\Toggle::make('required')
                                            ->label('Required')
                                            ->default(true)
                                            ->inline(false)
                                            ->hiddenLabel()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Toggle if field input is required during scan.')
                                            ->columnSpan(1),

                                        Forms\Components\Toggle::make('compare')
                                            ->label('Compare')
                                            ->default(true)
                                            ->reactive()
                                            ->inline(false)
                                            ->hiddenLabel()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Compare scanned/actual value against expected value.')
                                            ->columnSpan(1),

                                        Forms\Components\TextInput::make('tolerance')
                                            ->numeric()
                                            ->placeholder('Tolerance')
                                            ->hiddenLabel()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Allowed deviation margin (e.g. 0.02).')
                                            ->visible(fn($get) => $get('compare') === true)
                                            ->columnSpan(1),

                                        Forms\Components\Toggle::make('is_editable_in_table')
                                            ->label('Editable')
                                            ->default(false)
                                            ->inline(false)
                                            ->hiddenLabel()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Allows editing this value inside scanner table view.')
                                            ->columnSpan(1),

                                        Forms\Components\Toggle::make('is_quickcheck')
                                            ->label('Quick Check')
                                            ->default(false)
                                            ->inline(false)
                                            ->hiddenLabel()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Enable quick check icon to auto-copy expected value.')
                                            ->columnSpan(1),

                                        Forms\Components\Toggle::make('is_apply_validate')
                                            ->label('Apply Rule')
                                            ->default(false)
                                            ->inline(false)
                                            ->hiddenLabel()
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Apply automatic decision rules if this field fails check.')
                                            ->columnSpan(2),
                                    ]),
                            ])
                            ->itemLabel(fn(array $state): ?string => $state['field_name'] ?? $state['field'] ?? 'New Field')
                            ->default([]),
                    ])
            ]);
    }

    /**
     * @return array<string, string>
     */
    private static function getFieldOptions(?string $productTypeId): array
    {
        $standardFields = [
            'code' => 'Product Code (Standard)',
            'barcode' => 'Barcode (Standard)',
            'qr_code' => 'QR Code (Standard)',
            'name' => 'Product Name (Standard)',
            'location_id' => 'Location (Standard)',
            'quantity' => 'Quantity (Standard)',
        ];

        if (! $productTypeId) {
            return $standardFields;
        }

        $dynamicFields = ProductTypeField::query()
            ->where('product_type_id', $productTypeId)
            ->where('is_active', true)
            ->get()
            ->mapWithKeys(function (ProductTypeField $field): array {
                $label = $field->field_label;
                if ($field->field_type) {
                    $label .= ' (' . $field->field_type . ')';
                }

                return [$field->field_name => $label];
            })
            ->all();

        return array_merge($standardFields, $dynamicFields);
    }

    private static function resolveFieldLabel(string $fieldName, ?string $productTypeId): string
    {
        return self::getFieldOptions($productTypeId)[$fieldName] ?? $fieldName;
    }

    /**
     * Try multiple relative state paths because the field sits inside nested schema containers.
     */
    private static function getProductTypeIdFromState(callable $get): ?string
    {
        foreach ([
            'product_type_id',
            '../product_type_id',
            '../../product_type_id',
            '../../../product_type_id',
            '../../../../product_type_id',
        ] as $path) {
            $productTypeId = $get($path);

            if (filled($productTypeId)) {
                return (string) $productTypeId;
            }
        }

        return null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('productType.name')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('productType')
                    ->relationship('productType', 'name')
            ])
            ->actions([
                Actions\EditAction::make(),
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
            'index' => Pages\ListScanConfigs::route('/'),
            'create' => Pages\CreateScanConfig::route('/create'),
            'edit' => Pages\EditScanConfig::route('/{record}/edit'),
        ];
    }
}
