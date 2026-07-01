<?php

namespace App\Filament\Stock\Resources\ProductTypeResource\RelationManagers;

use Filament\Forms;
use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductTypeFieldsRelationManager extends RelationManager
{
    protected static string $relationship = 'productTypeFields';

    protected static ?string $title = 'Dynamic Fields';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('field_name')
                    ->required()
                    ->regex('/^[a-z0-9_]+$/')
                    ->helperText('Use snake_case, e.g. weight_g, serial_no')
                    ->maxLength(255),
                Forms\Components\TextInput::make('field_label')
                    ->required()
                    ->helperText('Display label, e.g. Weight (grams)')
                    ->maxLength(255),
                Forms\Components\Select::make('field_type')
                    ->required()
                    ->options([
                        'text' => 'Text',
                        'number' => 'Integer Number',
                        'decimal' => 'Decimal Number',
                        'date' => 'Date',
                        'textarea' => 'Long Text / Textarea',
                        'select' => 'Dropdown / Select Option',
                        'boolean' => 'Yes/No (Boolean)',
                    ]),
                Forms\Components\Toggle::make('required')
                    ->label('Is Required Field?')
                    ->default(false),
                Forms\Components\Toggle::make('show_in_creation_form')
                    ->label('Show in Create Form?')
                    ->default(false),
                Forms\Components\Toggle::make('show_in_table_by_default')
                    ->label('Show in Scanner Table?')
                    ->default(false),
                Forms\Components\Toggle::make('is_active')
                    ->label('Is Field Active?')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('field_label')
            ->columns([
                Tables\Columns\TextColumn::make('field_name')->sortable(),
                Tables\Columns\TextColumn::make('field_label')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('field_type')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\IconColumn::make('required')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('show_in_creation_form')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('show_in_table_by_default')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make(),
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
}
