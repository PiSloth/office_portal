<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductImportBatchResource;
use App\Models\ProductImportBatch;
use App\Models\ProductType;
use App\Services\ProductImportService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('download_template')
                ->label('Download Template')
                ->color('gray')
                ->icon('heroicon-o-arrow-down-tray')
                ->form([
                    Forms\Components\Select::make('product_type_id')
                        ->label('Product Type')
                        ->options(ProductType::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->placeholder('Generic template')
                        ->helperText('Leave empty for a generic template, or pick a product type to include its active dynamic fields.'),
                ])
                ->action(function (array $data) {
                    $query = array_filter([
                        'product_type_id' => $data['product_type_id'] ?? null,
                    ]);

                    return redirect()->route('product-import.template', $query);
                }),
            Action::make('import')
                ->label('Import CSV')
                ->color('info')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Forms\Components\Select::make('product_type_id')
                        ->options(ProductType::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->required(),
                    Forms\Components\FileUpload::make('file')
                        ->required()
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv']),
                ])
                ->action(function (array $data) {
                    $filePath = $data['file'];
                    $originalName = basename($filePath);

                    // Create Import Batch
                    $batch = ProductImportBatch::create([
                        'file_path' => $filePath,
                        'file_name' => $originalName,
                        'product_type_id' => $data['product_type_id'],
                        'status' => 'PENDING',
                        'total_rows' => 0,
                        'imported_rows' => 0,
                        'failed_rows' => 0,
                        'created_by' => auth()->id(),
                    ]);

                    // Execute Import
                    $service = app(ProductImportService::class);
                    $service->import($batch);

                    // Refresh model instance
                    $batch->refresh();

                    if ($batch->status === 'SUCCESS') {
                        Notification::make()
                            ->title('Import Completed')
                            ->body("Successfully imported {$batch->imported_rows} products.")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Import Completed with Warnings')
                            ->body("Imported {$batch->imported_rows} products. {$batch->failed_rows} rows failed. View import logs for details.")
                            ->warning()
                            ->send();
                    }
                }),
            Action::make('import_history')
                ->label('Import History')
                ->color('gray')
                ->icon('heroicon-o-clock')
                ->url(ProductImportBatchResource::getUrl()),
        ];
    }
}
