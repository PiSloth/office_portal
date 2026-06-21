<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScanConfigResource\Pages;
use App\Models\ScanConfig;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
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
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Set $set) => $set('config_json.fields', [])),
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
                                Forms\Components\Select::make('field')
                                    ->required()
                                    ->options(function (callable $get) {
                                        $productTypeId = $get('../../product_type_id');
                                        
                                        $standardFields = [
                                            'code' => 'Product Code (Standard)',
                                            'barcode' => 'Barcode (Standard)',
                                            'qr_code' => 'QR Code (Standard)',
                                            'name' => 'Product Name (Standard)',
                                            'location_id' => 'Location (Standard)',
                                        ];

                                        if (!$productTypeId) {
                                            return $standardFields;
                                        }

                                        $dynamicFields = \App\Models\ProductTypeField::where('product_type_id', $productTypeId)
                                            ->where('is_active', true)
                                            ->pluck('field_label', 'field_name')
                                            ->toArray();

                                        return array_merge($standardFields, $dynamicFields);
                                    })
                                    ->columnSpan(2),
                                
                                Forms\Components\Select::make('source')
                                    ->required()
                                    ->options([
                                        'product' => 'Expected (from Product Master Data)',
                                        'check' => 'Actual Input Only (e.g. checker notes)',
                                    ])
                                    ->default('product')
                                    ->columnSpan(2),

                                Forms\Components\Toggle::make('required')
                                    ->label('Is Required?')
                                    ->default(true)
                                    ->columnSpan(1),

                                Forms\Components\Toggle::make('compare')
                                    ->label('Compare Expected?')
                                    ->default(true)
                                    ->reactive()
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('tolerance')
                                    ->numeric()
                                    ->label('Tolerance (if numeric)')
                                    ->helperText('e.g. 0.02 for weight deviations')
                                    ->visible(fn (Get $get) => $get('compare') === true)
                                    ->columnSpan(2),
                            ])
                            ->columns(8)
                            ->itemLabel(fn (array $state): ?string => ($state['field'] ?? null) ? "Field: " . $state['field'] : 'New Field')
                            ->default([]),
                    ])
            ]);
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
