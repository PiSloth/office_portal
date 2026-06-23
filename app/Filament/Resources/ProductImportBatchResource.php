<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Filament\Resources\ProductImportBatchResource\Pages;
use App\Models\ProductImportBatch;
use App\Models\ProductImportLog;
use App\Services\ProductImportService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use BackedEnum;
use UnitEnum;

class ProductImportBatchResource extends Resource
{
    use HasPermissionGates;

    protected static string $permissionPrefix = 'product-import-batches';

    protected static ?string $model = ProductImportBatch::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationLabel = 'Import History';

    protected static UnitEnum|string|null $navigationGroup = 'Inventory';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('productType.name')->label('Product Type')->sortable(),
                Tables\Columns\TextColumn::make('file_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        'SUCCESS' => 'success',
                        'FAILED' => 'danger',
                        'ROLLBACKED' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_rows')->label('Total Rows')->alignCenter(),
                Tables\Columns\TextColumn::make('imported_rows')->label('Imported')->alignCenter(),
                Tables\Columns\TextColumn::make('failed_rows')->label('Failed')->alignCenter(),
                Tables\Columns\TextColumn::make('creator.name')->label('Uploaded By')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\Action::make('view_errors')
                    ->label('View Logs')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->modalHeading('Import Row Error Details')
                    ->modalSubmitAction(false) // hide submit button
                    ->form(function (ProductImportBatch $record) {
                        $logs = ProductImportLog::where('batch_id', $record->id)
                            ->orderBy('row_number', 'asc')
                            ->get();

                        $html = '<div class="space-y-3 overflow-y-auto max-h-96 text-sm">';
                        foreach ($logs as $log) {
                            $statusColor = $log->status === 'SUCCESS' ? 'text-green-600' : 'text-red-600 font-semibold';
                            $errors = $log->errors_json ? implode(', ', array_values($log->errors_json)) : 'None';

                            $html .= "<div class='border-b pb-2 dark:border-gray-700'>";
                            $html .= "<strong>Row #{$log->row_number}</strong> - <span class='{$statusColor}'>" . strtoupper($log->status) . "</span><br>";
                            if ($log->status === 'FAILED') {
                                $html .= "<span class='text-red-500'>Errors: {$errors}</span><br>";
                            }
                            $html .= "<span class='text-gray-500 dark:text-gray-400'>Data: " . json_encode($log->data_json) . "</span>";
                            $html .= "</div>";
                        }
                        $html .= '</div>';

                        return [
                            Forms\Components\Placeholder::make('logs_placeholder')
                                ->content(new HtmlString($html)),
                        ];
                    }),

                Actions\Action::make('rollback')
                    ->label('Rollback')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rollback Uploaded Products')
                    ->modalDescription('Are you sure you want to rollback this import? This will delete all products imported in this batch.')
                    ->visible(fn(ProductImportBatch $record) => in_array($record->status, ['SUCCESS', 'FAILED']) && $record->imported_rows > 0)
                    ->action(function (ProductImportBatch $record) {
                        $service = app(ProductImportService::class);
                        $service->rollback($record);

                        Notification::make()
                            ->title('Batch Rollbacked')
                            ->body('Successfully deleted imported products from this batch.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductImportBatches::route('/'),
        ];
    }
}
