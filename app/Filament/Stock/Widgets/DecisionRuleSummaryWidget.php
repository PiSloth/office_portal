<?php

namespace App\Filament\Stock\Widgets;

use App\Models\DecisionRule;
use App\Models\Decision;
use App\Models\ProductCheck;
use App\Models\ProductCheckValue;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class DecisionRuleSummaryWidget extends TableWidget
{
    protected static ?string $heading = 'Decision Rule Statistics Summary';

    protected int | string | array $columnSpan = 'full';

    protected function getFilteredDecisionQuery(DecisionRule $record, ?string $sessionId, ?string $categoryId, ?string $subCategoryId)
    {
        $query = Decision::where('decision_rule_id', $record->id);

        if ($sessionId || $categoryId || $subCategoryId) {
            $query->whereHas('productCheck', function ($q) use ($sessionId, $categoryId, $subCategoryId) {
                if ($sessionId) {
                    $q->where('check_session_id', $sessionId);
                }
                if ($categoryId || $subCategoryId) {
                    $q->whereHas('product', function ($pq) use ($categoryId, $subCategoryId) {
                        if ($categoryId) {
                            $pq->where('category_id', $categoryId);
                        }
                        if ($subCategoryId) {
                            $pq->where('sub_category_id', $subCategoryId);
                        }
                    });
                }
            });
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                DecisionRule::query()
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Decision Rule')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('criteria_field')
                    ->label('Check Field')
                    ->sortable(),
                TextColumn::make('decisions_count')
                    ->label('Decisions Triggered')
                    ->state(function (DecisionRule $record) {
                        $sessionId = $this->tableFilters['check_session_id']['value'] ?? null;
                        $categoryId = $this->tableFilters['category_id']['value'] ?? null;
                        $subCategoryId = $this->tableFilters['sub_category_id']['value'] ?? null;

                        return $this->getFilteredDecisionQuery($record, $sessionId, $categoryId, $subCategoryId)->count();
                    })
                    ->alignEnd(),
                TextColumn::make('total_quantity')
                    ->label('Total Quantity')
                    ->state(function (DecisionRule $record) {
                        if (strtolower($record->criteria_field) !== 'quantity') {
                            return '-';
                        }
                        
                        $sessionId = $this->tableFilters['check_session_id']['value'] ?? null;
                        $categoryId = $this->tableFilters['category_id']['value'] ?? null;
                        $subCategoryId = $this->tableFilters['sub_category_id']['value'] ?? null;

                        $checkIds = $this->getFilteredDecisionQuery($record, $sessionId, $categoryId, $subCategoryId)
                            ->pluck('product_check_id')
                            ->filter();

                        return ProductCheck::whereIn('id', $checkIds)->sum('quantity') ?: 0;
                    })
                    ->alignEnd(),
                TextColumn::make('total_weight')
                    ->label('Total Weight')
                    ->state(function (DecisionRule $record) {
                        if (strtolower($record->criteria_field) !== 'weight') {
                            return '-';
                        }

                        $sessionId = $this->tableFilters['check_session_id']['value'] ?? null;
                        $categoryId = $this->tableFilters['category_id']['value'] ?? null;
                        $subCategoryId = $this->tableFilters['sub_category_id']['value'] ?? null;

                        $checkIds = $this->getFilteredDecisionQuery($record, $sessionId, $categoryId, $subCategoryId)
                            ->pluck('product_check_id')
                            ->filter();

                        return ProductCheckValue::whereIn('product_check_id', $checkIds)
                            ->where('field_name', 'weight')
                            ->get()
                            ->sum(fn ($val) => (float)$val->actual_value) ?: 0;
                    })
                    ->alignEnd(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('check_session_id')
                    ->label('Check Session')
                    ->options(\App\Models\CheckSession::pluck('name', 'id'))
                    ->query(fn ($query) => $query),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(\App\Models\Category::pluck('name', 'id'))
                    ->query(fn ($query) => $query),
                Tables\Filters\SelectFilter::make('sub_category_id')
                    ->label('Sub-Category')
                    ->options(\App\Models\SubCategory::pluck('name', 'id'))
                    ->searchable()
                    ->query(fn ($query) => $query),
            ])
            ->defaultPaginationPageOption(5);
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}
