<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\DecisionResource;
use App\Models\Decision;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class OpenDecisionsWidget extends TableWidget
{
    protected static ?string $heading = 'Open Decisions';

    protected int | string | array $columnSpan = 'full';

    protected function getTableQuery(): Builder|Relation|null
    {
        return Decision::query()
            ->where('action_status', 'OPEN')
            ->with(['productCheck.product', 'decisionType', 'assignedTo', 'decisionBy'])
            ->latest()
            ->limit(8);
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('productCheck.product.code')
                ->label('Product Code')
                ->searchable()
                ->sortable(),
            TextColumn::make('productCheck.id')
                ->label('Check ID')
                ->sortable(),
            TextColumn::make('decisionType.name')
                ->label('Decision Type')
                ->sortable(),
            TextColumn::make('assignedTo.name')
                ->label('Assigned To')
                ->sortable(),
            TextColumn::make('decisionBy.name')
                ->label('Decision By')
                ->sortable(),
            TextColumn::make('action_status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'OPEN' => 'warning',
                    'IN_PROGRESS' => 'info',
                    'DONE' => 'success',
                    'REJECTED' => 'danger',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('view')
                ->label('View')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn (Decision $record): string => DecisionResource::getUrl('view', ['record' => $record])),
        ];
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}
