<?php

namespace App\Filament\Stock\Widgets;

use App\Models\CheckSession;
use App\Models\Category;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ProductPivotReportWidget extends Widget
{
    protected string $view = 'filament.stock.widgets.product-pivot-report-widget';

    protected int | string | array $columnSpan = 'full';

    public ?int $selectedSessionId = null;
    public ?int $selectedCategoryId = null;
    public string $stockStatusFilter = 'all'; // 'all', 'not_started', 'over_stock', 'loss_stock'
    public string $displayMode = 'qty'; // 'qty', 'weight'

    public function mount(): void
    {
        $this->selectedSessionId = CheckSession::latest()->first()?->id;
    }

    public function getViewData(): array
    {
        $sessions = CheckSession::latest()->get();
        $categories = Category::orderBy('name')->get();

        $query = DB::table('products as p')
            ->leftJoin('sub_categories as sc', 'p.sub_category_id', '=', 'sc.id')
            ->leftJoin('product_attribute_values as branch_pav', function($join) {
                $join->on('p.id', '=', 'branch_pav.product_id')
                     ->where('branch_pav.field_name', '=', 'branch_id');
            })
            ->leftJoin('branches as b', 'branch_pav.value', '=', DB::raw('b.id'))
            ->leftJoin('product_attribute_values as weight_pav', function($join) {
                $join->on('p.id', '=', 'weight_pav.product_id')
                     ->where('weight_pav.field_name', '=', 'weight_g');
            });

        // Apply Category filter if selected
        if ($this->selectedCategoryId) {
            $query->where('p.category_id', $this->selectedCategoryId);
        }

        // Join checks
        if ($this->selectedSessionId) {
            $query->leftJoin('product_checks as pc', function($join) {
                $join->on('p.id', '=', 'pc.product_id')
                     ->where('pc.check_session_id', '=', $this->selectedSessionId)
                     ->whereNull('pc.deleted_at');
            });
            $query->leftJoin('product_import_batches as pib', 'p.import_batch_id', '=', 'pib.id')
                ->where(function($q) {
                    $q->where('pib.check_session_id', $this->selectedSessionId)
                      ->orWhere(function($sq) {
                          $sq->where('p.created_during_pickup', 1)
                            ->whereNotNull('pc.id');
                      });
                });
        } else {
            $query->leftJoin('product_checks as pc', function($join) {
                $join->on('p.id', '=', 'pc.product_id')
                     ->whereNull('pc.deleted_at');
            });
        }

        $query->select([
            DB::raw('MIN(sc.id) as sub_category_id'),
            'sc.name as sub_category_name',
            'b.id as branch_id',
            'b.name as branch_name',
            
            // Quantity metrics
            DB::raw('COUNT(DISTINCT CASE WHEN (p.created_during_pickup = 0 OR p.created_during_pickup IS NULL) THEN p.id END) as imported_count'),
            DB::raw('COUNT(DISTINCT CASE WHEN (p.created_during_pickup = 1) THEN p.id END) as during_created_count'),
            DB::raw('COUNT(DISTINCT CASE WHEN (p.created_during_pickup = 0 OR p.created_during_pickup IS NULL) AND pc.id IS NOT NULL THEN p.id END) as checked_count'),

            // Weight metrics
            DB::raw('SUM(CASE WHEN (p.created_during_pickup = 0 OR p.created_during_pickup IS NULL) THEN CAST(COALESCE(weight_pav.value, 0) AS DECIMAL(12,4)) ELSE 0 END) as imported_weight'),
            DB::raw('SUM(CASE WHEN (p.created_during_pickup = 1) THEN CAST(COALESCE(weight_pav.value, 0) AS DECIMAL(12,4)) ELSE 0 END) as during_created_weight'),
            DB::raw('SUM(CASE WHEN (p.created_during_pickup = 0 OR p.created_during_pickup IS NULL) AND pc.id IS NOT NULL THEN CAST(COALESCE(weight_pav.value, 0) AS DECIMAL(12,4)) ELSE 0 END) as checked_weight')
        ]);

        $rows = $query->groupBy('sc.name', 'b.id', 'b.name')
            ->orderBy('sc.name', 'asc')
            ->orderBy('b.name', 'asc')
            ->get();

        // Structure reportData
        $reportData = [];
        foreach ($rows as $row) {
            $subCategoryName = $row->sub_category_name ?? 'No Sub Category';
            $branchName = $row->branch_name ?? 'No Branch';

            $isQtyMode = $this->displayMode === 'qty';
            $imported = $isQtyMode ? (int) $row->imported_count : (float) $row->imported_weight;
            $duringCreated = $isQtyMode ? (int) $row->during_created_count : (float) $row->during_created_weight;
            $checked = $isQtyMode ? (int) $row->checked_count : (float) $row->checked_weight;
            $balance = ($duringCreated + $checked) - $imported;

            // Apply stock status filters at the branch level
            if ($this->stockStatusFilter === 'not_started') {
                if (($checked + $duringCreated) != 0) {
                    continue;
                }
            } elseif ($this->stockStatusFilter === 'over_stock') {
                if ($balance <= 0) {
                    continue;
                }
            } elseif ($this->stockStatusFilter === 'loss_stock') {
                if ($balance >= 0) {
                    continue;
                }
            }

            $scKey = trim(mb_strtolower($subCategoryName));

            if (!isset($reportData[$scKey])) {
                $reportData[$scKey] = [
                    'name' => $subCategoryName,
                    'imported' => 0,
                    'during_created' => 0,
                    'checked' => 0,
                    'balance' => 0,
                    'branches' => []
                ];
            }

            $branchKey = trim(mb_strtolower($branchName));
            if (!isset($reportData[$scKey]['branches'][$branchKey])) {
                $reportData[$scKey]['branches'][$branchKey] = [
                    'name' => $branchName,
                    'imported' => 0,
                    'during_created' => 0,
                    'checked' => 0,
                    'balance' => 0
                ];
            }

            $reportData[$scKey]['branches'][$branchKey]['imported'] += $imported;
            $reportData[$scKey]['branches'][$branchKey]['during_created'] += $duringCreated;
            $reportData[$scKey]['branches'][$branchKey]['checked'] += $checked;
            $reportData[$scKey]['branches'][$branchKey]['balance'] += $balance;

            // Accumulate parent counts
            $reportData[$scKey]['imported'] += $imported;
            $reportData[$scKey]['during_created'] += $duringCreated;
            $reportData[$scKey]['checked'] += $checked;
            $reportData[$scKey]['balance'] += $balance;
        }

        // If a subcategory has no branches after filtering, we remove it from the list
        foreach ($reportData as $id => $data) {
            if (empty($data['branches'])) {
                unset($reportData[$id]);
            }
        }

        // Sort subcategories alphabetically by name
        uksort($reportData, function($a, $b) use ($reportData) {
            return strcasecmp($reportData[$a]['name'], $reportData[$b]['name']);
        });

        // Sort branches alphabetically for each subcategory
        foreach ($reportData as $scKey => &$scData) {
            uasort($scData['branches'], function($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
        }
        unset($scData);

        // Calculate Grand Totals
        $grandTotal = [
            'imported' => 0,
            'during_created' => 0,
            'checked' => 0,
            'balance' => 0,
        ];

        foreach ($reportData as $scData) {
            $grandTotal['imported'] += $scData['imported'];
            $grandTotal['during_created'] += $scData['during_created'];
            $grandTotal['checked'] += $scData['checked'];
            $grandTotal['balance'] += $scData['balance'];
        }

        return [
            'sessions' => $sessions,
            'categories' => $categories,
            'reportData' => $reportData,
            'grandTotal' => $grandTotal,
        ];
    }
}
