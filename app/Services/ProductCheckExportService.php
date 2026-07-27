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
    public function downloadAll(?\Illuminate\Database\Eloquent\Builder $query = null, ?int $productTypeId = null, ?array $selectedColumns = null): StreamedResponse
    {
        $filename = 'checked-products-' . now()->format('Y-m-d-His') . '.xlsx';

        return response()->streamDownload(function () use ($query, $productTypeId, $selectedColumns) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $headerStyle = (new Style())
                ->setFontBold()
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor(Color::rgb(31, 41, 55));

            $fieldNames = $this->getDistinctFieldNames($query, $productTypeId);
            $this->writeMasterSheet($writer, $headerStyle, $fieldNames, $query, $productTypeId, $selectedColumns);

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    protected function writeMasterSheet(Writer $writer, Style $headerStyle, array $fieldNames, ?\Illuminate\Database\Eloquent\Builder $query = null, ?int $productTypeId = null, ?array $selectedColumns = null): void
    {
        $writer->addNewSheetAndMakeItCurrent();

        $sheetName = 'Master Checks';
        if ($productTypeId) {
            $productType = \App\Models\ProductType::find($productTypeId);
            if ($productType) {
                $sheetName = substr($productType->name, 0, 30);
            }
        }
        $writer->getCurrentSheet()->setName($sheetName);

        $standardHeaderMap = [
            'id' => 'ID',
            'session' => 'Session',
            'location' => 'Location',
            'category' => 'Category Name',
            'sub_category' => 'Sub Category Name',
            'product_code' => 'Product Code',
            'product_name' => 'Product Name',
            'quantity' => 'Quantity',
            'checker' => 'Checker',
            'status' => 'Status',
            'checked_at' => 'Checked At',
            'remark' => 'Remark',
        ];

        $standardValueMap = [
            'id' => fn (ProductCheck $c) => $c->id,
            'session' => fn (ProductCheck $c) => $c->checkSession?->name,
            'location' => fn (ProductCheck $c) => $c->location?->name ?? $c->location?->code ?? 'N/A',
            'category' => fn (ProductCheck $c) => $c->product?->category?->name,
            'sub_category' => fn (ProductCheck $c) => $c->product?->subCategory?->name,
            'product_code' => fn (ProductCheck $c) => $c->product?->code ?? $c->barcode,
            'product_name' => fn (ProductCheck $c) => $c->product?->name ?? 'Unmatched Product',
            'quantity' => fn (ProductCheck $c) => $c->quantity,
            'checker' => fn (ProductCheck $c) => $c->checkedBy?->name,
            'status' => fn (ProductCheck $c) => $c->result_status,
            'checked_at' => fn (ProductCheck $c) => optional($c->checked_at)->toDateTimeString(),
            'remark' => fn (ProductCheck $c) => $c->remark,
        ];

        if (empty($selectedColumns)) {
            $selectedColumns = array_keys($standardHeaderMap);
            foreach ($fieldNames as $fn) {
                $selectedColumns[] = 'field_' . $fn;
            }
            $selectedColumns[] = 'decisions';
            $selectedColumns[] = 'comments';
        }

        $headers = [];
        foreach ($selectedColumns as $colKey) {
            if (isset($standardHeaderMap[$colKey])) {
                $headers[] = $standardHeaderMap[$colKey];
            } else if (str_starts_with($colKey, 'field_')) {
                $fName = substr($colKey, 6);
                $label = $this->formatFieldLabel($fName);
                $headers[] = "{$label} Expected";
                $headers[] = "{$label} Actual";
                $headers[] = "{$label} Difference";
                $headers[] = "{$label} Status";
            } else if ($colKey === 'decisions') {
                $headers[] = 'Decisions';
            } else if ($colKey === 'comments') {
                $headers[] = 'Comments';
            }
        }

        $writer->addRow(Row::fromValues($headers, $headerStyle));

        $query = $query ?? ProductCheck::query();

        if ($productTypeId) {
            $query->whereHas('product', function ($q) use ($productTypeId) {
                $q->where('product_type_id', $productTypeId);
            });
        }

        $query->with([
            'checkSession',
            'location',
            'product.category',
            'product.subCategory',
            'product.attributeValues',
            'checkedBy',
            'checkValues',
            'decisions.decisionType',
            'decisions.assignedTo',
            'decisions.decisionBy',
            'decisions.comments.user',
        ])
            ->orderByDesc('checked_at')
            ->chunk(250, function ($checks) use ($writer, $selectedColumns, $standardValueMap) {
                foreach ($checks as $check) {
                    $decisionsText = null;
                    if (in_array('decisions', $selectedColumns, true)) {
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
                    }

                    $commentsText = null;
                    if (in_array('comments', $selectedColumns, true)) {
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
                    }

                    $valuesByField = $check->checkValues->keyBy('field_name');
                    $row = [];

                    foreach ($selectedColumns as $colKey) {
                        if (isset($standardValueMap[$colKey])) {
                            $row[] = $standardValueMap[$colKey]($check);
                        } else if (str_starts_with($colKey, 'field_')) {
                            $fName = substr($colKey, 6);
                            $value = $valuesByField->get($fName);

                            if ($value) {
                                $row[] = $value->expected_value;
                                $row[] = $value->actual_value;
                                $row[] = $value->difference_value;
                                $row[] = $value->status;
                            } else if ($check->product) {
                                $productVal = match ($fName) {
                                    'location_id', 'location' => $check->location?->name ?? $check->product?->location_id,
                                    'category_id', 'category' => $check->product?->category?->name ?? $check->product?->category_id,
                                    'sub_category_id', 'sub_category' => $check->product?->subCategory?->name ?? $check->product?->sub_category_id,
                                    'code', 'barcode', 'qr_code', 'name', 'description', 'status' => $check->product->{$fName},
                                    'quantity' => $check->quantity,
                                    default => $check->product->attributeValues->firstWhere('field_name', $fName)?->value,
                                };
                                $row[] = $productVal;
                                $row[] = $productVal;
                                $row[] = 0;
                                $row[] = 'PASS';
                            } else {
                                $row[] = null;
                                $row[] = null;
                                $row[] = null;
                                $row[] = null;
                            }
                        } else if ($colKey === 'decisions') {
                            $row[] = $decisionsText;
                        } else if ($colKey === 'comments') {
                            $row[] = $commentsText;
                        }
                    }

                    $writer->addRow(Row::fromValues([
                        ...$row,
                    ]));
                }
            });
    }

    protected function getDistinctFieldNames(?\Illuminate\Database\Eloquent\Builder $query = null, ?int $productTypeId = null): array
    {
        $valuesFieldNames = [];
        $typeFieldNames = [];
        $configFieldNames = [];

        if ($productTypeId) {
            $typeFieldNames = \App\Models\ProductTypeField::where('product_type_id', $productTypeId)
                ->where('is_active', true)
                ->pluck('field_name')
                ->toArray();
        }

        if ($query) {
            $effectiveQuery = clone $query;
            if ($productTypeId) {
                $effectiveQuery->whereHas('product', function ($q) use ($productTypeId) {
                    $q->where('product_type_id', $productTypeId);
                });
            }

            $checkIds = (clone $effectiveQuery)->pluck('product_checks.id');
            if ($checkIds->isNotEmpty()) {
                $valuesFieldNames = ProductCheckValue::whereIn('product_check_id', $checkIds)
                    ->whereNotNull('field_name')
                    ->pluck('field_name')
                    ->toArray();
            }

            if (empty($typeFieldNames)) {
                $productTypeIds = \App\Models\Product::whereIn('id', (clone $effectiveQuery)->whereNotNull('product_checks.product_id')->select('product_checks.product_id'))
                    ->pluck('product_type_id')
                    ->filter()
                    ->unique()
                    ->toArray();

                if (!empty($productTypeIds)) {
                    $typeFieldNames = \App\Models\ProductTypeField::whereIn('product_type_id', $productTypeIds)
                        ->where('is_active', true)
                        ->pluck('field_name')
                        ->toArray();
                }
            }

            $scanConfigIds = (clone $effectiveQuery)->whereNotNull('product_checks.scan_config_id')->pluck('product_checks.scan_config_id')->filter()->unique();
            if ($scanConfigIds->isNotEmpty()) {
                $configs = \App\Models\ScanConfig::whereIn('id', $scanConfigIds)->get();
                foreach ($configs as $config) {
                    foreach (data_get($config->config_json, 'fields', []) as $f) {
                        if (!empty($f['field'])) {
                            $configFieldNames[] = $f['field'];
                        }
                    }
                }
            }
        }

        if (empty($valuesFieldNames)) {
            $valuesFieldNames = ProductCheckValue::query()
                ->select('field_name')
                ->whereNotNull('field_name')
                ->distinct()
                ->pluck('field_name')
                ->toArray();
        }

        if (empty($typeFieldNames)) {
            $typeFieldNames = \App\Models\ProductTypeField::where('is_active', true)
                ->pluck('field_name')
                ->toArray();
        }

        return collect(array_merge($typeFieldNames, $valuesFieldNames, $configFieldNames))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    protected function formatFieldLabel(string $fieldName): string
    {
        return ucfirst(str_replace(['_', '-'], ' ', $fieldName));
    }
}
