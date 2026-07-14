<?php

namespace App\Filament\Stock\Resources;

use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Filament\Stock\Resources\ProductResource\Pages;
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

    protected static string $permissionPrefix = 'products';

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
                            ->afterStateUpdated(fn($state, Set $set) => [
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
                            ->afterStateUpdated(fn($state, Set $set) => $set('sub_category_id', null)),

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
                        Forms\Components\TextInput::make('quantity')
                            ->numeric()
                            ->default(0)
                            ->required(),
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
                                'boolean' => Forms\Components\Select::make($field->field_name)
                                    ->options([
                                        '1' => 'Yes',
                                        '0' => 'No',
                                    ]),
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
        $columns = [
            Tables\Columns\TextColumn::make('id')->sortable(),
            Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('productType.name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('location.code')->label('Location'),
            Tables\Columns\TextColumn::make('category.name')->label('Category'),
            Tables\Columns\TextColumn::make('subCategory.name')->label('Sub-Category'),
            Tables\Columns\TextColumn::make('quantity')
                ->label('Quantity')
                ->sortable()
                ->formatStateUsing(fn($state) => $state ?? 0),
            Tables\Columns\TextColumn::make('barcode')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('qr_code')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('created_during_pickup')
                ->label('Created During Pickup')
                ->badge()
                ->formatStateUsing(fn(bool $state): string => $state ? 'Yes' : '-')
                ->color(fn(bool $state): string => $state ? 'success' : 'gray')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'ACTIVE' => 'success',
                    'SUSPENDED' => 'danger',
                    default => 'gray',
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('created_by')
                ->label('Created By')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_by')
                ->label('Updated By')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        try {
            $dynamicFields = \App\Models\ProductTypeField::query()
                ->select(['field_name', 'field_label'])
                ->distinct()
                ->get();

            foreach ($dynamicFields as $field) {
                $columns[] = Tables\Columns\TextColumn::make('attr_' . $field->field_name)
                    ->label($field->field_label)
                    ->state(function (\App\Models\Product $record) use ($field) {
                        $val = $record->attributeValues->where('field_name', $field->field_name)->first();
                        return $val ? $val->value : '-';
                    })
                    ->toggleable(isToggledHiddenByDefault: true);
            }
        } catch (\Exception $e) {
            // Ignore during migrations
        }

        return $table
            ->columns($columns)
            ->filters([
                Tables\Filters\SelectFilter::make('productType')
                    ->relationship('productType', 'name'),
                Tables\Filters\SelectFilter::make('location')
                    ->relationship('location', 'code'),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('subCategory')
                    ->label('Sub-Category')
                    ->relationship('subCategory', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('check_session_id')
                    ->label('Check Session')
                    ->options(\App\Models\CheckSession::pluck('name', 'id'))
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): void {
                        $sessionId = $data['value'] ?? null;
                        if ($sessionId) {
                            $query->whereHas('productChecks', fn ($q) => $q->where('check_session_id', $sessionId));
                        }
                    }),
                 Tables\Filters\TernaryFilter::make('is_checked')
                    ->label('Check Status')
                    ->placeholder('All Products')
                    ->trueLabel('Checked')
                    ->falseLabel('Not Checked')
                    ->queries(
                        true: function ($query) {
                            $sessionId = request()->input('tableFilters.check_session_id.value')
                                ?? \App\Models\CheckSession::latest('started_at')->first()?->id;

                            if ($sessionId) {
                                $session = \App\Models\CheckSession::find($sessionId);
                                if ($session && $session->product_type_id) {
                                    $query->where('product_type_id', $session->product_type_id);
                                }
                                return $query->whereHas('productChecks', fn ($q) => $q->where('check_session_id', $sessionId));
                            }
                            return $query->whereHas('productChecks');
                        },
                        false: function ($query) {
                            $sessionId = request()->input('tableFilters.check_session_id.value')
                                ?? \App\Models\CheckSession::latest('started_at')->first()?->id;

                            if ($sessionId) {
                                $session = \App\Models\CheckSession::find($sessionId);
                                if ($session && $session->product_type_id) {
                                    $query->where('product_type_id', $session->product_type_id);
                                }
                                return $query->whereDoesntHave('productChecks', fn ($q) => $q->where('check_session_id', $sessionId));
                            }
                            return $query->whereDoesntHave('productChecks');
                        },
                    ),
            ])
            ->recordActions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with('attributeValues');
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
