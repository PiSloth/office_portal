<?php

namespace App\Filament\Stock\Resources;

use App\Filament\Stock\Resources\DeletedProductCheckResource\Pages;
use App\Models\ProductCheck;
use BackedEnum;
use Filament\Actions;
use Filament\Resources\Resource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class DeletedProductCheckResource extends Resource
{
    protected static ?string $model = ProductCheck::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-trash';

    protected static UnitEnum|string|null $navigationGroup = 'Inspection';

    protected static ?string $navigationLabel = 'Deleted Checks';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('product.code')
                    ->label('Product')
                    ->state(fn (ProductCheck $record): ?string => $record->product?->code ?? $record->barcode)
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product Name')
                    ->state(fn (ProductCheck $record): ?string => $record->product?->name ?? 'Unmatched Product')
                    ->limit(32)
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')->label('Quantity')->sortable(),
                Tables\Columns\TextColumn::make('checkSession.name')->label('Session')->sortable(),
                Tables\Columns\TextColumn::make('result_status')->badge()->color(fn (string $state): string => match ($state) {
                    'PASS' => 'success',
                    'FAIL' => 'danger',
                    'WARNING' => 'warning',
                    'UNMATCHED' => 'danger',
                    default => 'gray',
                })->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')->dateTime()->label('Deleted At')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('check_session_id')
                    ->label('Session')
                    ->relationship('checkSession', 'name'),
                Tables\Filters\SelectFilter::make('result_status')
                    ->options([
                        'PASS' => 'Pass',
                        'FAIL' => 'Fail',
                        'WARNING' => 'Warning',
                        'UNMATCHED' => 'Unmatched',
                    ]),
            ])
            ->actions([
                Actions\Action::make('restore')
                    ->label('Restore')
                    ->action(fn (ProductCheck $record) => $record->restore())
                    ->requiresConfirmation()
                    ->color('success'),
                Actions\DeleteAction::make()
                    ->label('Delete Permanently')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(fn (ProductCheck $record) => $record->forceDelete()),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('restore')
                        ->label('Restore Selected')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->restore())
                        ->requiresConfirmation()
                        ->color('success'),
                    Actions\BulkAction::make('forceDelete')
                        ->label('Delete Permanently')
                        ->action(fn (\Illuminate\Database\Eloquent\Collection $records) => $records->each->forceDelete())
                        ->requiresConfirmation()
                        ->color('danger'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeletedProductChecks::route('/'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->onlyTrashed();
    }
}
