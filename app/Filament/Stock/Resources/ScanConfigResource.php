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
                    ->schema([
                        Forms\Components\Repeater::make('config_json.fields')
                            ->label('Fields Checklist')
                            ->schema([
                                Grid::make(1)->schema([
                                    Forms\Components\TextInput::make('field_name')
                                        ->label('Field Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->helperText('A readable label for this scan field.'),

                                    Forms\Components\Select::make('field')
                                        ->label('Product Field')
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
                                        ->helperText('Select the actual field key from the chosen product type.'),

                                    Forms\Components\Select::make('source')
                                        ->required()
                                        ->options([
                                            'product' => 'Expected (from Product Master Data)',
                                            'check' => 'Actual Input Only (e.g. checker notes)',
                                        ])
                                        ->default('product'),

                                    Forms\Components\Toggle::make('required')
                                        ->label('Is Required?')
                                        ->default(true),

                                    Forms\Components\Toggle::make('compare')
                                        ->label('Compare Expected?')
                                        ->default(true)
                                        ->reactive(),

                                    Forms\Components\TextInput::make('tolerance')
                                        ->numeric()
                                        ->label('Tolerance (if numeric)')
                                        ->helperText('e.g. 0.02 for weight deviations')
                                        ->visible(fn(Get $get) => $get('compare') === true),

                                    Forms\Components\Toggle::make('is_editable_in_table')
                                        ->label('Editable in Scanner Table?')
                                        ->default(false),
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
