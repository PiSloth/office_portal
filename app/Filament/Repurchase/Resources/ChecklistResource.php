<?php

namespace App\Filament\Repurchase\Resources;

use App\Filament\Repurchase\Resources\ChecklistResource\Pages;
use App\Modules\Core\Workflow\Models\Checklist;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChecklistResource extends Resource
{
    use \App\Filament\Resources\Concerns\HasPermissionGates;
    
    protected static string $permissionPrefix = 'checklists';
    protected static ?string $model = Checklist::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static \UnitEnum|string|null $navigationGroup = 'Configuration';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                Forms\Components\Select::make('product_type_id')
                    ->relationship('productType', 'name')
                    ->nullable()
                    ->helperText('Optional: Scope this checklist to a specific product type.'),

                 \Filament\Schemas\Components\Section::make('Checklist Items')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Schemas\Components\Grid::make(12)
                            ->schema([
                                \Filament\Forms\Components\Placeholder::make('header_label')
                                    ->hiddenLabel()
                                    ->content('Label')
                                    ->columnSpan(5),
                                \Filament\Forms\Components\Placeholder::make('header_sort_order')
                                    ->hiddenLabel()
                                    ->content('Sort Order')
                                    ->columnSpan(3),
                                \Filament\Forms\Components\Placeholder::make('header_required')
                                    ->hiddenLabel()
                                    ->content('Required')
                                    ->columnSpan(2),
                                \Filament\Forms\Components\Placeholder::make('header_active')
                                    ->hiddenLabel()
                                    ->content('Active')
                                    ->columnSpan(2),
                            ])
                            ->extraAttributes(['class' => 'font-semibold border-b pb-2 mb-2 hidden md:grid']),

                        Forms\Components\Repeater::make('items')
                            ->relationship(modifyQueryUsing: fn ($query) => $query->orderBy('sort_order', 'asc'))
                            ->reorderable('sort_order')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->required()
                                    ->maxLength(255)
                                    ->hiddenLabel()
                                    ->columnSpan(5),
                                Forms\Components\TextInput::make('sort_order')
                                    ->numeric()
                                    ->default(0)
                                    ->hiddenLabel()
                                    ->columnSpan(3),
                                Forms\Components\Toggle::make('is_required')
                                    ->label('Required')
                                    ->inline(false)
                                    ->default(true)
                                    ->hiddenLabel()
                                    ->columnSpan(2),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->inline(false)
                                    ->default(true)
                                    ->hiddenLabel()
                                    ->columnSpan(2),
                            ])
                            ->columns(12)
                            ->itemLabel(fn(array $state): ?string => $state['label'] ?? 'New Checklist Item'),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('productType.name')
                    ->label('Product Type')
                    ->placeholder('All Products')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items Count')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListChecklists::route('/'),
            'create' => Pages\CreateChecklist::route('/create'),
            'edit' => Pages\EditChecklist::route('/{record}/edit'),
        ];
    }
}
