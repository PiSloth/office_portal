<?php

namespace App\Filament\Stock\Widgets;

use App\Models\Product;
use App\Models\CheckSession;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class CategoryCheckReportWidget extends TableWidget
{
    protected static ?string $heading = 'Sub Category Check Report';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $sessionId = $this->tableFilters['check_session']['value'] ?? null;

                $subQuery = Product::query()
                    ->select('products.category_id', 'products.sub_category_id')
                    ->selectRaw("CONCAT(coalesce(products.category_id, 0), '-', coalesce(products.sub_category_id, 0)) as id")
                    ->selectRaw('count(distinct products.id) as total_imported');

                if ($sessionId) {
                    $subQuery->selectRaw('count(distinct product_checks.product_id) as total_checked')
                        ->leftJoin('product_checks', function ($join) use ($sessionId) {
                            $join->on('product_checks.product_id', '=', 'products.id')
                                ->where('product_checks.check_session_id', '=', $sessionId)
                                ->whereNull('product_checks.deleted_at');
                        });
                } else {
                    $subQuery->selectRaw('0 as total_checked');
                }

                $subQuery->groupBy('products.category_id', 'products.sub_category_id');

                $modelInstance = new Product();
                $modelInstance->setTable('grouped_products');
                $modelInstance->setKeyName('id');

                return $modelInstance->newQuery()
                    ->fromSub($subQuery, 'grouped_products')
                    ->select('grouped_products.*')
                    ->with(['category', 'subCategory']);
            })
            ->columns([
                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('subCategory.name')
                    ->label('Sub Category')
                    ->default('---')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('total_imported')
                    ->label('Imported Products')
                    ->alignEnd(),
                TextColumn::make('total_checked')
                    ->label('Checked Products')
                    ->alignEnd(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('check_session')
                    ->label('Check Session')
                    ->options(CheckSession::pluck('name', 'id'))
                    ->default(fn() => CheckSession::latest()->first()?->id)
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query) => $query),
            ])
            ->defaultSort('category_id', 'asc');
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}
