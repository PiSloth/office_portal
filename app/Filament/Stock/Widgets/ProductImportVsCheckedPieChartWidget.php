<?php

namespace App\Filament\Stock\Widgets;

use App\Models\CheckSession;
use App\Models\Product;
use App\Models\ProductCheck;
use App\Models\ProductImportBatch;
use Filament\Widgets\ChartWidget;

class ProductImportVsCheckedPieChartWidget extends ChartWidget
{
    protected ?string $pollingInterval = '5s';

    protected ?string $heading = 'Imported vs Checked Products';

    public ?string $filter = null;

    protected int | string | array $columnSpan = 1;

    protected ?string $maxHeight = '220px';

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getFilters(): ?array
    {
        $sessions = CheckSession::query()
            ->orderBy('created_at', 'desc')
            ->pluck('name', 'id')
            ->all();

        return $sessions ?: ['' => 'No active sessions'];
    }

    protected function getData(): array
    {
        $sessionId = $this->filter;

        if (!$sessionId) {
            $sessionId = CheckSession::query()->orderBy('created_at', 'desc')->value('id');
        }

        if (!$sessionId) {
            return [
                'datasets' => [
                    [
                        'data' => [0, 0],
                        'backgroundColor' => ['#22c55e', '#eab308'],
                    ],
                ],
                'labels' => ['Checked Products', 'Pending Checks'],
            ];
        }

        // Get total imported products for the selected session
        $importBatchIds = ProductImportBatch::where('check_session_id', $sessionId)->pluck('id');
        $importedCount = Product::whereIn('import_batch_id', $importBatchIds)->count();

        // Get checked products count for the selected session
        $checkedCount = ProductCheck::where('check_session_id', $sessionId)->count();

        $pendingCount = max(0, $importedCount - $checkedCount);

        // Update heading dynamically to show the active session name
        $sessionName = CheckSession::where('id', $sessionId)->value('name') ?? 'Session';
        $this->heading = "{$sessionName}: Imported vs Checked";

        return [
            'datasets' => [
                [
                    'label' => 'Products Count',
                    'data' => [$checkedCount, $pendingCount],
                    'backgroundColor' => [
                        '#22c55e', // Green for Checked
                        '#eab308', // Yellow for Pending
                    ],
                ],
            ],
            'labels' => [
                "Checked Products ({$checkedCount})",
                "Pending Checks ({$pendingCount})",
            ],
        ];
    }
}
