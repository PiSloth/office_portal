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
                 Tables\Columns\TextColumn::make('result_status')
                    ->html()
                    ->state(function (ProductCheck $record): string {
                        $status = $record->result_status;
                        
                        $colorClass = match ($status) {
                            'PASS' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 dark:bg-emerald-500/20 ring-emerald-600/10 dark:ring-emerald-500/20',
                            'FAIL' => 'bg-rose-500/10 text-rose-700 dark:text-rose-400 dark:bg-rose-500/20 ring-rose-600/10 dark:ring-rose-500/20',
                            'WARNING' => 'bg-amber-500/10 text-amber-700 dark:text-amber-400 dark:bg-amber-500/20 ring-amber-600/10 dark:ring-amber-500/20',
                            'UNMATCHED' => 'bg-rose-500/10 text-rose-700 dark:text-rose-400 dark:bg-rose-500/20 ring-rose-600/10 dark:ring-rose-500/20',
                            default => 'bg-gray-500/10 text-gray-700 dark:text-gray-400 dark:bg-gray-500/20 ring-gray-600/10 dark:ring-gray-500/20',
                        };
                        
                        $statusLabel = match ($status) {
                            'PASS' => 'Pass',
                            'FAIL' => 'Fail',
                            'WARNING' => 'Warning',
                            'UNMATCHED' => 'Unmatched',
                            default => $status,
                        };
                        
                        $badges = "<span class='inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {$colorClass}'>{$statusLabel}</span>";
                        
                        if ($status === 'UNMATCHED' && $record->product?->created_during_pickup) {
                            $badges .= " <span class='inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 dark:bg-emerald-500/20 ring-emerald-600/10 dark:ring-emerald-500/20 ml-1'>Created</span>";
                        }
                        
                        return $badges;
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
