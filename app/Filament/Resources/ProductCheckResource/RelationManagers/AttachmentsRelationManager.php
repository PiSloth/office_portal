<?php

namespace App\Filament\Resources\ProductCheckResource\RelationManagers;

use Filament\Actions;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

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
                Tables\Columns\TextColumn::make('file_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('file_type')->label('Type')->sortable(),
                Tables\Columns\TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 1) . ' KB' : 'N/A'),
                Tables\Columns\TextColumn::make('uploadedBy.name')->label('Uploaded By')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Attachment Preview')
                    ->modalContent(fn ($record) => new HtmlString(
                        '<div class="p-4 text-center">'
                        . '<img src="' . e(Storage::disk('public')->url($record->file_path)) . '" '
                        . 'alt="' . e($record->file_name) . '" '
                        . 'class="mx-auto max-h-[70vh] w-full max-w-4xl object-contain rounded-xl shadow-sm" />'
                        . '</div>'
                    ))
                    ->modalWidth('xl')
                    ->modalSubmitAction(false),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
