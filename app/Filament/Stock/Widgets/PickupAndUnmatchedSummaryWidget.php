<?php

namespace App\Filament\Stock\Widgets;

use App\Models\Product;
use App\Models\ProductCheck;
use App\Models\CheckSession;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PickupAndUnmatchedSummaryWidget extends TableWidget
{
    protected static ?string $heading = 'Pickup & Unmatched Check Summary';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                CheckSession::query()->latest('started_at')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Session')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_during_pickup_count')
                    ->label('Created During Pickup')
                    ->state(function (CheckSession $record) {
                        return Product::where('created_during_pickup', true)
                            ->whereHas('productChecks', fn ($q) => $q->where('check_session_id', $record->id))
                            ->count();
                    })
                    ->alignEnd(),
                TextColumn::make('unmatched_pending_count')
                    ->label('Unmatched (Pending Creation)')
                    ->state(function (CheckSession $record) {
                        return ProductCheck::where('check_session_id', $record->id)
                            ->where('result_status', 'UNMATCHED')
                            ->whereNull('product_id')
                            ->count();
                    })
                    ->alignEnd(),
                TextColumn::make('unchecked_count')
                    ->label('Unchecked Products')
                    ->state(function (CheckSession $record) {
                        $query = Product::query();
                        if ($record->product_type_id) {
                            $query->where('product_type_id', $record->product_type_id);
                        }
                        return $query->whereDoesntHave('productChecks', fn ($q) => $q->where('check_session_id', $record->id))
                            ->count();
                    })
                    ->alignEnd(),
            ])
            ->defaultPaginationPageOption(5);
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}
