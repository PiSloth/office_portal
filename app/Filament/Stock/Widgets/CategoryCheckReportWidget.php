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
                $checkedBy = $this->tableFilters['checked_by']['value'] ?? null;

                $subQuery = Product::query()
                    ->select('products.category_id', 'products.sub_category_id')
                    ->selectRaw("CONCAT(coalesce(products.category_id, 0), '-', coalesce(products.sub_category_id, 0)) as id")
                    ->selectRaw('count(distinct case when products.created_during_pickup = 0 or products.created_during_pickup is null then products.id end) as total_imported')
                    ->selectRaw('count(distinct case when products.created_during_pickup = 1 then products.id end) as total_created_during_pickup');

                if ($sessionId) {
                    $subQuery->selectRaw('count(distinct product_checks.product_id) as total_checked')
                        ->leftJoin('product_checks', function ($join) use ($sessionId, $checkedBy) {
                            $join->on('product_checks.product_id', '=', 'products.id')
                                ->where('product_checks.check_session_id', '=', $sessionId)
                                ->whereNull('product_checks.deleted_at');
                            if ($checkedBy) {
                                $join->where('product_checks.checked_by', '=', $checkedBy);
                            }
                        });
                } else {
                    $subQuery->selectRaw('0 as total_checked');
                }

                $subQuery->groupBy('products.category_id', 'products.sub_category_id');

                return GroupedProductReport::query()
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
                TextColumn::make('total_created_during_pickup')
                    ->label('Created During Pickup')
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
                Tables\Filters\SelectFilter::make('checked_by')
                    ->label('Checked By')
                    ->options(\App\Models\User::pluck('name', 'id'))
                    ->searchable()
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query) => $query),
                Tables\Filters\SelectFilter::make('progress_status')
                    ->label('Progress Status')
                    ->options([
                        'COMPLETED' => 'Complete Check',
                        'PROCESSING' => 'Processing Check',
                        'NOT_STARTED' => 'No Start Check',
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): void {
                        $value = $data['value'] ?? null;
                        if (!$value) {
                            return;
                        }

                        match ($value) {
                            'COMPLETED' => $query->whereRaw('total_checked >= total_imported'),
                            'PROCESSING' => $query->whereRaw('total_checked > 0 AND total_checked < total_imported'),
                            'NOT_STARTED' => $query->whereRaw('total_checked = 0'),
                            default => null,
                        };
                    }),
            ])
            ->defaultSort('category_id', 'asc');
    }

    public static function canView(): bool
    {
        return auth()->check();
    }
}

class GroupedProductReport extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'grouped_products';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class, 'category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(\App\Models\SubCategory::class, 'sub_category_id');
    }
}
