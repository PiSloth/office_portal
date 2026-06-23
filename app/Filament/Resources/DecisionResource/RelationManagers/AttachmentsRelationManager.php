<?php

namespace App\Filament\Resources\DecisionResource\RelationManagers;

use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Attachments';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('file_path')
                    ->label('Preview')
                    ->disk('public')
                    ->visibility('public')
                    ->checkFileExistence(false)
                    ->imageHeight(60)
                    ->imageWidth(80),
                Tables\Columns\TextColumn::make('file_name')->searchable(),
                Tables\Columns\TextColumn::make('file_type'),
                Tables\Columns\TextColumn::make('uploadedBy.name')->label('Uploaded By'),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
