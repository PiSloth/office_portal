<?php

namespace App\Filament\Resources\DecisionResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class HistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'histories';

    protected static ?string $title = 'Status History';

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('old_status')->badge(),
            Tables\Columns\TextColumn::make('new_status')->badge(),
            Tables\Columns\TextColumn::make('changer.name')->label('Changed By'),
            Tables\Columns\TextColumn::make('remark')->limit(80),
            Tables\Columns\TextColumn::make('created_at')->dateTime(),
        ]);
    }
}
