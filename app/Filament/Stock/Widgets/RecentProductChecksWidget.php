<?php

namespace App\Filament\Stock\Widgets;

use App\Filament\Stock\Resources\ProductCheckResource;
use App\Models\ProductCheck;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class RecentProductChecksWidget extends TableWidget
{
    protected ?string $pollingInterval = '5s';

    protected static ?string $heading = 'Recent Product Checks';

    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder|Relation|null
    {
        return ProductCheck::query()
            ->with(['product', 'checkSession', 'checkedBy'])
            ->latest('checked_at')
            ->limit(8);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('product.code')
                ->label('Product')
                ->searchable()
                ->sortable(),
            TextColumn::make('product.name')
                ->label('Product Name')
                ->limit(28),
            TextColumn::make('checkSession.name')
                ->label('Session')
                ->sortable(),
            TextColumn::make('checkedBy.name')
                ->label('Checked By')
                ->sortable(),
            TextColumn::make('result_status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'PASS' => 'success',
                    'FAIL' => 'danger',
                    'WARNING' => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('checked_at')
                ->dateTime()
                ->since(),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('view')
                ->label('View')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (ProductCheck $record): string => ProductCheckResource::getUrl('view', ['record' => $record])),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}
