<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProductImportBatchResource;
use App\Models\ProductImportBatch;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class RecentProductImportsWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Product Imports';

    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder|Relation|null
    {
        return ProductImportBatch::query()->latest()->limit(8);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('file_name')
                ->label('File')
                ->searchable()
                ->limit(35),
            TextColumn::make('productType.name')
                ->label('Product Type')
                ->sortable(),
            TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'PENDING' => 'warning',
                    'SUCCESS' => 'success',
                    'FAILED' => 'danger',
                    'ROLLBACKED' => 'gray',
                    default => 'gray',
                }),
            TextColumn::make('imported_rows')
                ->label('Imported')
                ->alignCenter(),
            TextColumn::make('failed_rows')
                ->label('Failed')
                ->alignCenter(),
            TextColumn::make('created_at')
                ->dateTime()
                ->since(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('open_history')
                ->label('Open History')
                ->url(ProductImportBatchResource::getUrl())
                ->icon('heroicon-o-arrow-top-right-on-square'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}
