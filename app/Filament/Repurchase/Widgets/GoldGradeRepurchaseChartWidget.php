<?php

namespace App\Filament\Repurchase\Widgets;

use App\Models\Branch;
use App\Modules\Purchase\Models\PurchaseItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;
use Illuminate\Support\Carbon;

class GoldGradeRepurchaseChartWidget extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Most Repurchased Gold Grades (by Gram)';

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '300px';

    protected function getType(): string
    {
        return 'bar';
    }

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('branch_id')
                ->label('Branch')
                ->options([
                    'all' => 'All Branches',
                    ...Branch::pluck('code', 'id')->all(),
                ])
                ->default('all')
                ->searchable(),
            DatePicker::make('start_date')
                ->label('Start Date')
                ->default(now()),
            DatePicker::make('end_date')
                ->label('End Date')
                ->default(now()),
        ])->columns(3);
    }

    protected function getData(): array
    {
        $branchId = $this->filters['branch_id'] ?? 'all';
        $startDate = Carbon::parse($this->filters['start_date'] ?? now())->startOfDay();
        $endDate = Carbon::parse($this->filters['end_date'] ?? now())->endOfDay();

        $gradeLabels = [
            '16' => '16 ပဲ',
            '15' => '15 ပဲ',
            '14.2' => 'ဒင်္ဂါး (14.2)',
            '14' => '14 ပဲ',
            '13' => '13 ပဲ',
            '12' => '12 ပဲ',
            '10' => '10 ပဲ',
            '8' => '8 ပဲ',
            '4' => '4 ပဲ',
        ];

        $gradeGrams = [];
        foreach ($gradeLabels as $key => $label) {
            $gradeGrams[$key] = [
                'label' => $label,
                'gb_product' => 0.0,
                'other_product' => 0.0,
            ];
        }

        $items = PurchaseItem::query()
            ->whereHas('purchaseRequest', function ($query) use ($branchId, $startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
                if ($branchId !== 'all') {
                    $query->where('branch_id', $branchId);
                }
            })
            ->get();
        foreach ($items as $item) {
            $fields = $item->dynamic_fields_json;
            if (!$fields) {
                continue;
            }

            $goldGrade = $fields['goldList'] ?? null;
            $purchaseType = $fields['purchase_type'] ?? null;
            $weightGram = floatval($fields['goldWeightGram'] ?? 0);

            if ($goldGrade && isset($gradeGrams[$goldGrade])) {
                if ($purchaseType === 'gb_product') {
                    $gradeGrams[$goldGrade]['gb_product'] += $weightGram;
                } elseif ($purchaseType === 'other_product') {
                    $gradeGrams[$goldGrade]['other_product'] += $weightGram;
                }
            }
        }

        // Sort by total weight descending
        uasort($gradeGrams, function ($a, $b) {
            $totalA = $a['gb_product'] + $a['other_product'];
            $totalB = $b['gb_product'] + $b['other_product'];
            return $totalB <=> $totalA;
        });

        $labels = [];
        $gbData = [];
        $otherData = [];

        foreach ($gradeGrams as $grade) {
            $labels[] = $grade['label'];
            $gbData[] = round($grade['gb_product'], 2);
            $otherData[] = round($grade['other_product'], 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'GP Product',
                    'data' => $gbData,
                    'backgroundColor' => '#fc950f',
                    'borderColor' => '#eab308',
                ],
                [
                    'label' => 'Other Product',
                    'data' => $otherData,
                    'backgroundColor' => '#7531bc',
                    'borderColor' => '#5d2596',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
