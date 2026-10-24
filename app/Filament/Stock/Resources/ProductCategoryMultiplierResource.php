<?php

namespace App\Filament\Stock\Resources;

use App\Filament\Stock\Resources\ProductCategoryMultiplierResource\Pages;
use App\Models\ProductCategoryMultiplier;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class ProductCategoryMultiplierResource extends Resource
{
    protected static ?string $model = ProductCategoryMultiplier::class;

    protected static ?string $modelLabel = 'Product Category Multiplier';

    protected static ?string $pluralModelLabel = 'Product Category Multipliers';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calculator';

    protected static UnitEnum|string|null $navigationGroup = 'Promotion';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Category Multiplier Details')
                    ->description('Set name and price calculation multiplier for this product category (e.g. 999 -> 1.0 or 0.999)')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Product Category Name')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->placeholder('e.g. 999, 18K, Gold Ring'),
                        Forms\Components\TextInput::make('multiplier')
                            ->label('Multiplier')
                            ->required()
                            ->numeric()
                            ->default(1.0)
                            ->step('any')
                            ->minValue(0)
                            ->helperText('Supports up to 16 decimal places. Used in formula: (total wastage / 2) * ((gold price * multiplier) / 16.606)'),
                        Forms\Components\Textarea::make('description')
                            ->label('Description / Notes')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Category Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('multiplier')
                    ->label('Multiplier')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => rtrim(rtrim((string)$state, '0'), '.')),
                Tables\Columns\TextColumn::make('promotion_items_count')
                    ->label('Assigned Items')
                    ->counts('promotionItems')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
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
            'index' => Pages\ListProductCategoryMultipliers::route('/'),
            'create' => Pages\CreateProductCategoryMultiplier::route('/create'),
            'edit' => Pages\EditProductCategoryMultiplier::route('/{record}/edit'),
        ];
    }
}
