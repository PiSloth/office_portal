<?php

namespace App\Filament\Repurchase\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Facades\DB;

class MissingGoldPriceAnnouncementsWidget extends TableWidget
{
    protected static ?string $heading = 'Missing Announcement Gold Prices (Import Required)';

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        $datesSubquery = DB::table('purchase_requests')
            ->selectRaw('DATE(created_at) as id')
            ->whereNull('deleted_at')
            ->union(
                DB::table('daily_price_histories')->selectRaw('DATE(created_at) as id')
            );

        return $table
            ->query(
                \App\Models\DailyPriceHistory::query()
                    ->fromSub($datesSubquery, 'daily_price_histories')
                    ->selectRaw('daily_price_histories.id as id, daily_price_histories.id as missing_date')
                    ->whereNotExists(function ($query) {
                        $query->selectRaw(1)
                            ->from('announcement_gold_prices')
                            ->whereRaw('DATE(announcement_gold_prices.announcement_datetime) = daily_price_histories.id');
                    })
                    ->groupBy('daily_price_histories.id')
            )
            ->columns([
                Tables\Columns\TextColumn::make('missing_date')
                    ->label('Date')
                    ->date('Y-m-d')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->default('Pending Import')
                    ->badge()
                    ->color('danger'),
            ])
            ->defaultSort('missing_date', 'desc')
            ->paginated(5);
    }
}
