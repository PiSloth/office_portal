<?php

namespace App\Filament\Stock\Resources;

use App\Filament\Stock\Resources\DecisionResource\Pages;
use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Filament\Stock\Resources\DecisionResource\RelationManagers\AttachmentsRelationManager;
use App\Filament\Stock\Resources\DecisionResource\RelationManagers\CommentsRelationManager;
use App\Filament\Stock\Resources\DecisionResource\RelationManagers\HistoriesRelationManager;
use App\Models\Decision;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class DecisionResource extends Resource
{
    use HasPermissionGates;

    protected static string $permissionPrefix = 'decisions';

    protected static ?string $model = Decision::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-flag';

    protected static UnitEnum|string|null $navigationGroup = 'Decision Management';

    protected static ?string $navigationLabel = 'Decisions';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Decision')->schema([
                Forms\Components\Select::make('product_check_id')
                    ->relationship('productCheck', 'id')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('decision_type_id')
                    ->relationship('decisionType', 'name')
                    ->required()
                    ->searchable(),
                Forms\Components\Select::make('action_status')
                    ->options([
                        'OPEN' => 'Open',
                        'IN_PROGRESS' => 'In Progress',
                        'DONE' => 'Done',
                        'REJECTED' => 'Rejected',
                    ])
                    ->required(),
                Forms\Components\Select::make('assigned_to')
                    ->relationship('assignedTo', 'name')
                    ->searchable()
                    ->nullable(),
                Forms\Components\Textarea::make('remark')
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('decision_by')
                    ->default(fn() => auth()->id()),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('productCheck.checkSession.name')
                    ->label('Check Session')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('productCheck.id')
                    ->label('Check ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('decisionRule.criteria_field')
                    ->label('Check Field')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('productCheck.product.code')
                    ->label('Product Code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('productCheck.product.name')
                    ->label('Product Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('productCheck.product.category.name')
                    ->label('Category'),
                Tables\Columns\TextColumn::make('productCheck.product.subCategory.name')
                    ->label('Sub-Category')
                    ->default('---'),
                Tables\Columns\TextColumn::make('decisionType.name')
                    ->label('Decision Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('action_status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'OPEN' => 'warning',
                        'IN_PROGRESS' => 'info',
                        'DONE' => 'success',
                        'REJECTED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned To')
                    ->sortable(),
                Tables\Columns\TextColumn::make('decisionBy.name')
                    ->label('Decision By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('remark')
                    ->label('Remark')
                    ->limit(30)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('check_session')
                    ->label('Check Session')
                    ->relationship('productCheck.checkSession', 'name'),
                Tables\Filters\SelectFilter::make('criteria_field')
                    ->label('Check Field')
                    ->relationship('decisionRule', 'criteria_field'),
                Tables\Filters\SelectFilter::make('action_status')
                    ->options([
                        'OPEN' => 'Open',
                        'IN_PROGRESS' => 'In Progress',
                        'DONE' => 'Done',
                        'REJECTED' => 'Rejected',
                    ]),
                Tables\Filters\SelectFilter::make('decision_type_id')
                    ->label('Decision Type')
                    ->relationship('decisionType', 'name'),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\Action::make('open_check')
                    ->label('View Check')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->url(fn(Decision $record) => ProductCheckResource::getUrl('view', ['record' => $record->product_check_id])),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('export')
                        ->label('Export to Excel')
                        ->icon('heroicon-o-document-arrow-down')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $headers = [
                                'Content-Type' => 'text/csv',
                                'Content-Disposition' => 'attachment; filename="decisions_export_' . now()->format('Ymd_His') . '.csv"',
                            ];

                            $callback = function () use ($records) {
                                $file = fopen('php://output', 'w');
                                fputcsv($file, [
                                    'ID',
                                    'Check Session',
                                    'Product Name',
                                    'Barcode',
                                    'Category',
                                    'Sub Category',
                                    'Check Field',
                                    'Database Value',
                                    'Checked Value',
                                    'Decision Type',
                                    'Status',
                                    'Assigned To',
                                    'Decision By',
                                    'Remark',
                                    'Created At'
                                ]);

                                foreach ($records as $record) {
                                    $field = $record->decisionRule?->criteria_field;
                                    $dbValue = null;
                                    $checkedValue = null;

                                    if ($field === 'quantity') {
                                        $dbValue = $record->productCheck?->product?->quantity;
                                        $checkedValue = $record->productCheck?->quantity;
                                    } else {
                                        $checkVal = $record->productCheck?->checkValues?->firstWhere('field_name', $field);
                                        if ($checkVal) {
                                            $dbValue = $checkVal->expected_value;
                                            $checkedValue = $checkVal->actual_value;
                                        }

                                        // Format boolean fields to Yes/No in export
                                        $fieldModel = \App\Models\ProductTypeField::where('product_type_id', $record->productCheck?->product?->product_type_id)
                                            ->where('field_name', $field)
                                            ->first();
                                        if ($fieldModel && $fieldModel->field_type === 'boolean') {
                                            $dbValue = ($dbValue === '1' || $dbValue === 1) ? 'Yes' : (($dbValue === '0' || $dbValue === 0) ? 'No' : $dbValue);
                                            $checkedValue = ($checkedValue === '1' || $checkedValue === 1) ? 'Yes' : (($checkedValue === '0' || $checkedValue === 0) ? 'No' : $checkedValue);
                                        }
                                    }

                                    fputcsv($file, [
                                        $record->id,
                                        $record->productCheck?->checkSession?->name,
                                        $record->productCheck?->product?->name,
                                        $record->productCheck?->barcode,
                                        $record->productCheck?->product?->category?->name,
                                        $record->productCheck?->product?->subCategory?->name,
                                        $field,
                                        $dbValue,
                                        $checkedValue,
                                        $record->decisionType?->name,
                                        $record->action_status,
                                        $record->assignedTo?->name,
                                        $record->decisionBy?->name,
                                        $record->remark,
                                        $record->created_at?->toDateTimeString(),
                                    ]);
                                }
                                fclose($file);
                            };

                            return response()->stream($callback, 200, $headers);
                        }),
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
            HistoriesRelationManager::class,
            AttachmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDecisions::route('/'),
            'view' => Pages\ViewDecision::route('/{record}'),
            'edit' => Pages\EditDecision::route('/{record}/edit'),
        ];
    }
}
