<?php

namespace App\Filament\Stock\Resources\ProductResource\Pages;

use App\Filament\Stock\Resources\ProductResource;
use App\Filament\Stock\Resources\ProductImportBatchResource;
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
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('selected_fields', null)),
                    Forms\Components\Hidden::make('selected_fields'),
                    Forms\Components\ViewField::make('selected_fields_picker')
                        ->view('filament.actions.custom-export-template')
                        ->viewData([
                            'productTypes' => \App\Models\ProductType::with(['productTypeFields' => fn ($q) => $q->where('is_active', true)])->get()->toArray(),
                        ])
                        ->columnSpanFull(),
                ])
                ->action(function (array $data) {
                    $query = array_filter([
                        'product_type_id' => $data['product_type_id'] ?? null,
                        'fields' => $data['selected_fields'] ?? null,
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
                    Forms\Components\Select::make('check_session_id')
                        ->label('Check Session')
                        ->options(\App\Models\CheckSession::query()
                            ->whereIn('status', ['DRAFT', 'OPEN'])
                            ->orderBy('created_at', 'desc')
                            ->pluck('name', 'id')
                            ->all())
                        ->nullable()
                        ->helperText('Select a check session to map this import batch directly to it.'),
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
                        'check_session_id' => $data['check_session_id'] ?? null,
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
