<?php

namespace App\Services;

use App\Models\ProductCheck;
use App\Models\ProductCheckValue;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductCheckExportService
{
    public function downloadAll(?\Illuminate\Database\Eloquent\Builder $query = null): StreamedResponse
    {
        $filename = 'checked-products-' . now()->format('Y-m-d-His') . '.xlsx';

        return response()->streamDownload(function () use ($query) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $headerStyle = (new Style())
                ->setFontBold()
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor(Color::rgb(31, 41, 55));

            $fieldNames = $this->getDistinctFieldNames();
            $this->writeMasterSheet($writer, $headerStyle, $fieldNames, $query);

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function writeMasterSheet(Writer $writer, Style $headerStyle, array $fieldNames, ?\Illuminate\Database\Eloquent\Builder $query = null): void
    {
        $writer->addNewSheetAndMakeItCurrent();
        $writer->getCurrentSheet()->setName('Master Checks');

        $headers = [
            'ID',
            'Session',
            'Product Code',
            'Product Name',
            'Quantity',
            'Checker',
            'Status',
            'Checked At',
            'Remark',
        ];

        foreach ($fieldNames as $fieldName) {
            $label = $this->formatFieldLabel($fieldName);
            $headers[] = "{$label} Expected";
            $headers[] = "{$label} Actual";
            $headers[] = "{$label} Difference";
            $headers[] = "{$label} Status";
        }

        $headers[] = 'Decisions';
        $headers[] = 'Comments';

        $writer->addRow(Row::fromValues($headers, $headerStyle));

        $query = $query ?? ProductCheck::query();

        $query->with([
            'checkSession',
            'product',
            'checkedBy',
            'checkValues',
            'decisions.decisionType',
            'decisions.assignedTo',
            'decisions.decisionBy',
            'decisions.comments.user',
        ])
            ->orderByDesc('checked_at')
            ->chunk(250, function ($checks) use ($writer, $fieldNames) {
                foreach ($checks as $check) {
                    $decisionsText = $check->decisions
                        ->map(function ($decision) {
                            return implode(' | ', array_filter([
                                '#'.$decision->id,
                                $decision->decisionType?->name ?? 'Decision',
                                'Status: ' . ($decision->action_status ?? 'N/A'),
                                'Assigned To: ' . ($decision->assignedTo?->name ?? 'Unassigned'),
                                'By: ' . ($decision->decisionBy?->name ?? 'N/A'),
                                'Remark: ' . ($decision->remark ?? ''),
                            ], fn ($item) => $item !== ''));
                        })
                        ->implode("\n");

                    $commentsText = $check->decisions
                        ->flatMap(function ($decision) {
                            return $decision->comments->map(function ($comment) use ($decision) {
                                return implode(' | ', array_filter([
                                    'Decision #'.$decision->id,
                                    ($comment->user?->name ?? 'User') . ': ' . ($comment->comment ?? ''),
                                    'Type: ' . ($comment->comment_type ?? 'N/A'),
                                    'At: ' . optional($comment->created_at)->toDateTimeString(),
                                ], fn ($item) => $item !== ''));
                            });
                        })
                        ->implode("\n");

                    $valuesByField = $check->checkValues->keyBy('field_name');
                    $row = [
                        $check->id,
                        $check->checkSession?->name,
                        $check->product?->code,
                        $check->product?->name,
                        $check->quantity,
                        $check->checkedBy?->name,
                        $check->result_status,
                        optional($check->checked_at)->toDateTimeString(),
                        $check->remark,
                    ];

                    foreach ($fieldNames as $fieldName) {
                        $value = $valuesByField->get($fieldName);

                        $row[] = $value?->expected_value;
                        $row[] = $value?->actual_value;
                        $row[] = $value?->difference_value;
                        $row[] = $value?->status;
                    }

                    $row[] = $decisionsText;
                    $row[] = $commentsText;

                    $writer->addRow(Row::fromValues([
                        ...$row,
                    ]));
                }
            });
    }

    protected function getDistinctFieldNames(): array
    {
        return ProductCheckValue::query()
            ->select('field_name')
            ->whereNotNull('field_name')
            ->distinct()
            ->orderBy('field_name')
            ->pluck('field_name')
            ->values()
            ->all();
    }

    protected function formatFieldLabel(string $fieldName): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $fieldName));
    }
}
