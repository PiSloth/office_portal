<?php

namespace App\Filament\Resources\ProductCheckResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductCheckValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'checkValues';

    protected static ?string $title = 'Check Values';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('field_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('expected_value')->limit(40),
                Tables\Columns\TextColumn::make('actual_value')->limit(40),
                Tables\Columns\TextColumn::make('difference_value')->limit(40),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PASS' => 'success',
                        'FAIL' => 'danger',
                        'WARNING' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
