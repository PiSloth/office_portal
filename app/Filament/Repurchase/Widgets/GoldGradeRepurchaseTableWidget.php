<?php

namespace App\Filament\Repurchase\Widgets;

use App\Models\Branch;
use App\Modules\Purchase\Models\PurchaseItem;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class GoldGradeRepurchaseTableWidget extends TableWidget
{
    protected static ?string $heading = 'Most Repurchased Gold Grades (by Gram) List';

    protected int | string | array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $branchId = $this->tableFilters['branch_id']['value'] ?? null;
                $startDate = $this->tableFilters['date_range']['start_date'] ?? null;
                $endDate = $this->tableFilters['date_range']['end_date'] ?? null;

                $startDateParsed = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->startOfDay();
                $endDateParsed = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

                $subquery = PurchaseItem::query()
                    ->selectRaw("
                        min(id) as id,
                        json_unquote(json_extract(dynamic_fields_json, '$.goldList')) as grade,
                        sum(case when json_unquote(json_extract(dynamic_fields_json, '$.purchase_type')) = 'gb_product' then cast(json_unquote(json_extract(dynamic_fields_json, '$.goldWeightGram')) as decimal(10,2)) else 0 end) as gp_product,
                        sum(case when json_unquote(json_extract(dynamic_fields_json, '$.purchase_type')) = 'other_product' then cast(json_unquote(json_extract(dynamic_fields_json, '$.goldWeightGram')) as decimal(10,2)) else 0 end) as oth_product,
                        sum(cast(json_unquote(json_extract(dynamic_fields_json, '$.goldWeightGram')) as decimal(10,2))) as total_gram
                    ")
                    ->whereHas('purchaseRequest', function ($query) use ($branchId, $startDateParsed, $endDateParsed) {
                        $query->whereBetween('created_at', [$startDateParsed, $endDateParsed]);
                        if ($branchId && $branchId !== 'all') {
                            $query->where('branch_id', $branchId);
                        }
                    })
                    ->groupByRaw("json_unquote(json_extract(dynamic_fields_json, '$.goldList'))")
                    ->havingRaw("grade IS NOT NULL AND grade != ''");

                return PurchaseItem::query()
                    ->withTrashed()
                    ->fromSub($subquery, 'purchase_items');
            })
            ->columns([
                Tables\Columns\TextColumn::make('grade')
                    ->label('Grade')
                    ->formatStateUsing(fn ($state) => match($state) {
                        '16' => '16 ပဲ',
                        '15' => '15 ပဲ',
                        '14.2' => 'ဒင်္ဂါး (14.2)',
                        '14' => '14 ပဲ',
                        '13' => '13 ပဲ',
                        '12' => '12 ပဲ',
                        '10' => '10 ပဲ',
                        '8' => '8 ပဲ',
                        '4' => '4 ပဲ',
                        default => $state . ' ပဲ',
                    })
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('gp_product')
                    ->label('GP Product (g)')
                    ->numeric(2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Grand Total')->numeric(2)),
                Tables\Columns\TextColumn::make('oth_product')
                    ->label('Oth Product (g)')
                    ->numeric(2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('')->numeric(2)),
                Tables\Columns\TextColumn::make('total_gram')
                    ->label('Total Gram (g)')
                    ->numeric(2)
                    ->alignEnd()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('')->numeric(2)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(Branch::pluck('code', 'id')->all())
                    ->placeholder('All Branches'),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(now()),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->default(now()),
                    ])
            ])
            ->defaultSort('total_gram', 'desc')
            ->paginated(false);
    }
}
