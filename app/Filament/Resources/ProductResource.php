<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Category;
use App\Models\SubCategory;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class ProductResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Product::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-archive-box';

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

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
                            ->afterStateUpdated(fn ($state, Set $set) => [
                                $set('category_id', null),
                                $set('sub_category_id', null),
                            ]),
                        Forms\Components\Select::make('location_id')
                            ->relationship('location', 'name')
                            ->nullable(),
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(function (callable $get) {
                                $productTypeId = $get('product_type_id');
                                if (!$productTypeId) {
                                    return Category::pluck('name', 'id');
                                }
                                return Category::where('product_type_id', $productTypeId)->pluck('name', 'id');
                            })
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(fn ($state, Set $set) => $set('sub_category_id', null)),
                        
                        Forms\Components\Select::make('sub_category_id')
                            ->label('Sub-Category')
                            ->options(function (callable $get) {
                                $categoryId = $get('category_id');
                                if (!$categoryId) {
                                    return [];
                                }
                                return SubCategory::where('category_id', $categoryId)->pluck('name', 'id');
                            })
                            ->nullable(),
                        
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('barcode')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('qr_code')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'ACTIVE' => 'Active',
                                'SUSPENDED' => 'Suspended',
                            ])
                            ->default('ACTIVE')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Dynamic Specifications')
                    ->schema(function (callable $get) {
                        $productTypeId = $get('product_type_id');
                        if (!$productTypeId) {
                            return [
                                Forms\Components\Placeholder::make('info')
                                    ->content('Select a Product Type to load dynamic properties.')
                            ];
                        }

                        $fields = \App\Models\ProductTypeField::where('product_type_id', $productTypeId)
                            ->where('is_active', true)
                            ->get();

                        if ($fields->isEmpty()) {
                            return [
                                Forms\Components\Placeholder::make('info')
                                    ->content('No dynamic fields configured for this product type.')
                            ];
                        }

                        $schema = [];
                        foreach ($fields as $field) {
                            $input = match ($field->field_type) {
                                'number' => Forms\Components\TextInput::make($field->field_name)->numeric()->integer(),
                                'decimal' => Forms\Components\TextInput::make($field->field_name)->numeric(),
                                'date' => Forms\Components\DatePicker::make($field->field_name),
                                'textarea' => Forms\Components\Textarea::make($field->field_name),
                                'select' => Forms\Components\TextInput::make($field->field_name), // simple input or select
                                'boolean' => Forms\Components\Toggle::make($field->field_name),
                                default => Forms\Components\TextInput::make($field->field_name),
                            };

                            $input->label($field->field_label)
                                ->required($field->required);

                            $schema[] = $input;
                        }

                        return $schema;
                    })
                    ->columns(2)
                    ->key('dynamic_attributes_section'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('productType.name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('location.code')->label('Location')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ACTIVE' => 'success',
                        'SUSPENDED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('productType')
                    ->relationship('productType', 'name'),
                Tables\Filters\SelectFilter::make('location')
                    ->relationship('location', 'code'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
