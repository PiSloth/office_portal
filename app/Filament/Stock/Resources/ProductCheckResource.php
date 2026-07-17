<?php

namespace App\Filament\Stock\Resources;

use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Filament\Stock\Resources\ProductCheckResource\Pages;
use App\Filament\Stock\Resources\ProductCheckResource\RelationManagers\AttachmentsRelationManager;
use App\Filament\Stock\Resources\ProductCheckResource\RelationManagers\CommentsRelationManager;
use App\Filament\Stock\Resources\ProductCheckResource\RelationManagers\DecisionsRelationManager;
use App\Filament\Stock\Resources\ProductCheckResource\RelationManagers\ProductCheckValuesRelationManager;
use App\Models\ProductCheck;
use App\Services\ProductCheckExportService;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use UnitEnum;

class ProductCheckResource extends Resource
{
    use HasPermissionGates;

    protected static string $permissionPrefix = 'product-checks';

    protected static ?string $model = ProductCheck::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static UnitEnum|string|null $navigationGroup = 'Inspection';

    protected static ?string $navigationLabel = 'Checked Products';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Check Summary')->schema([
                Forms\Components\Select::make('check_session_id')
                    ->relationship('checkSession', 'name')
                    ->required(),
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'code')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('checked_by')
                    ->relationship('checkedBy', 'name')
                    ->required(),
                Forms\Components\DateTimePicker::make('checked_at')
                    ->required(),
                Forms\Components\Select::make('result_status')
                    ->options([
                        'PASS' => 'Pass',
                        'FAIL' => 'Fail',
                        'WARNING' => 'Warning',
                        'UNMATCHED' => 'Unmatched',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('remark')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Review Summary')
                ->schema([
                    TextEntry::make('result_status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'PASS' => 'success',
                            'FAIL' => 'danger',
                            'WARNING' => 'warning',
                            'UNMATCHED' => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('solution_status')
                        ->label('Solution Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'Resolved' => 'success',
                            'Dismissed' => 'danger',
                            'In Progress' => 'info',
                            'Open' => 'warning',
                            'Pending Review' => 'gray',
                            default => 'gray',
                        })
                        ->state(fn (ProductCheck $record) => $record->solutionStatus()),
                    TextEntry::make('checkSession.name')->label('Session'),
                    TextEntry::make('scanConfig.name')->label('Scan Config'),
                    TextEntry::make('product.code')->label('Product Code'),
                    TextEntry::make('product.name')->label('Product Name'),
                    TextEntry::make('checkedBy.name')->label('Checked By'),
                    TextEntry::make('checked_at')->dateTime(),
                    TextEntry::make('remark')->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Failed Values')
                ->schema([
                    RepeatableEntry::make('failed_values')
                        ->label('Failed Attributes')
                        ->state(fn (ProductCheck $record) => $record->failedCheckValues()->map(fn ($value) => [
                            'field_name' => $value->field_name,
                            'expected_value' => $value->expected_value,
                            'actual_value' => $value->actual_value,
                            'difference_value' => $value->difference_value,
                            'status' => $value->status,
                        ])->all())
                        ->schema([
                            TextEntry::make('field_name')->label('Field'),
                            TextEntry::make('expected_value')->label('Expected'),
                            TextEntry::make('actual_value')->label('Actual'),
                            TextEntry::make('difference_value')->label('Difference'),
                            TextEntry::make('status')->badge(),
                        ])
                        ->columns(5),
                ]),

            Section::make('Matched Decision Rules')
                ->schema([
                    RepeatableEntry::make('matched_rules')
                        ->label('Matched Rules')
                        ->state(fn (ProductCheck $record) => $record->matchedDecisionRules()->map(fn ($rule) => [
                            'name' => $rule->name,
                            'criteria_field' => $rule->criteria_field,
                            'criteria_condition' => $rule->criteria_condition,
                            'decision_type' => $rule->decisionType?->name,
                        ])->all())
                        ->schema([
                            TextEntry::make('name')->label('Rule'),
                            TextEntry::make('criteria_field')->label('Field'),
                            TextEntry::make('criteria_condition')->label('Condition')->badge(),
                            TextEntry::make('decision_type')->label('Decision Type'),
                        ])
                        ->columns(4),
                ]),

            Section::make('Decisions and Discussion')
                ->schema([
                    RepeatableEntry::make('decision_review')
                        ->label('Decision Review')
                        ->state(fn (ProductCheck $record) => $record->decisions->map(fn ($decision) => [
                            'decision_type' => $decision->decisionType?->name,
                            'action_status' => $decision->action_status,
                            'assigned_to' => $decision->assignedTo?->name,
                            'decision_by' => $decision->decisionBy?->name,
                            'remark' => $decision->remark,
                            'comments' => $decision->comments->map(fn ($comment) => trim(
                                ($comment->user?->name ?? 'User') . ': ' . ($comment->comment ?? '')
                            ))->implode("\n"),
                        ])->all())
                        ->schema([
                            TextEntry::make('decision_type')->label('Type'),
                            TextEntry::make('action_status')->badge(),
                            TextEntry::make('assigned_to')->label('Assigned To'),
                            TextEntry::make('decision_by')->label('Decision By'),
                            TextEntry::make('remark')->label('Remark')->columnSpanFull(),
                            TextEntry::make('comments')->label('Comments')->listWithLineBreaks()->columnSpanFull(),
                        ])
                        ->columns(4),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $columns = [
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('product.code')
                    ->label('Product')
                    ->state(fn (ProductCheck $record): ?string => $record->product?->code ?? $record->barcode)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product Name')
                    ->state(fn (ProductCheck $record): ?string => $record->product?->name ?? 'Unmatched Product')
                    ->searchable()
                    ->limit(32),
                Tables\Columns\TextColumn::make('location.code')
                    ->label('Location')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('checkSession.name')
                    ->label('Session')
                    ->sortable(),
                Tables\Columns\TextColumn::make('scanConfig.name')
                    ->label('Scan Config')
                    ->sortable(),
                Tables\Columns\TextColumn::make('checkedBy.name')
                    ->label('Checked By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('result_status')
                    ->html()
                    ->state(function (ProductCheck $record): string {
                        $status = $record->result_status;
                        
                        $colorClass = match ($status) {
                            'PASS' => 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 dark:bg-emerald-500/20 ring-emerald-600/10 dark:ring-emerald-500/20',
                            'FAIL' => 'bg-rose-500/10 text-rose-700 dark:text-rose-400 dark:bg-rose-500/20 ring-rose-600/10 dark:ring-rose-500/20',
                            'WARNING' => 'bg-amber-500/10 text-amber-700 dark:text-amber-400 dark:bg-amber-500/20 ring-amber-600/10 dark:ring-amber-500/20',
                            'UNMATCHED' => 'bg-rose-500/10 text-rose-700 dark:text-rose-400 dark:bg-rose-500/20 ring-rose-600/10 dark:ring-rose-500/20',
                            default => 'bg-gray-500/10 text-gray-700 dark:text-gray-400 dark:bg-gray-500/20 ring-gray-600/10 dark:ring-gray-500/20',
                        };
                        
                        $statusLabel = match ($status) {
                            'PASS' => 'Pass',
                            'FAIL' => 'Fail',
                            'WARNING' => 'Warning',
                            'UNMATCHED' => 'Unmatched',
                            default => $status,
                        };
                        
                        $badges = "<span class='inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {$colorClass}'>{$statusLabel}</span>";
                        
                        if ($status === 'UNMATCHED' && $record->product?->created_during_pickup) {
                            $badges .= " <span class='inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 dark:bg-emerald-500/20 ring-emerald-600/10 dark:ring-emerald-500/20 ml-1'>Created</span>";
                        }
                        
                        return $badges;
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('checked_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.created_during_pickup')
                    ->label('Created During Pickup')
                    ->badge()
                    ->formatStateUsing(fn(?bool $state): string => $state ? 'Yes' : '-')
                    ->color(fn(?bool $state): string => $state ? 'success' : 'gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('remark')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
        ];

        try {
            $dynamicFields = \App\Models\ProductCheckValue::query()
                ->select('field_name')
                ->whereNotNull('field_name')
                ->distinct()
                ->pluck('field_name')
                ->toArray();

            foreach ($dynamicFields as $fieldName) {
                $columns[] = Tables\Columns\TextColumn::make('check_value_' . $fieldName)
                    ->label(ucfirst(str_replace(['_', '-'], ' ', $fieldName)))
                    ->state(function (ProductCheck $record) use ($fieldName) {
                        $val = $record->checkValues->where('field_name', $fieldName)->first();
                        return $val ? $val->actual_value : '-';
                    })
                    ->toggleable(isToggledHiddenByDefault: true);
            }
        } catch (\Exception $e) {
            // Ignore during migrations
        }

        return $table
            ->columns($columns)
            ->filters([
                Tables\Filters\SelectFilter::make('check_session_id')
                    ->label('Session')
                    ->relationship('checkSession', 'name'),
                Tables\Filters\SelectFilter::make('scan_config_id')
                    ->label('Scan Config')
                    ->relationship('scanConfig', 'name'),
                Tables\Filters\SelectFilter::make('location_id')
                    ->label('Location')
                    ->relationship('location', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Category')
                    ->relationship('product.category', 'name'),
                Tables\Filters\SelectFilter::make('subCategory')
                    ->label('Sub-Category')
                    ->relationship('product.subCategory', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('result_status')
                    ->options([
                        'PASS' => 'Pass',
                        'FAIL' => 'Fail',
                        'WARNING' => 'Warning',
                        'UNMATCHED' => 'Unmatched',
                    ]),
                Tables\Filters\SelectFilter::make('checked_by')
                    ->label('Checked By')
                    ->relationship('checkedBy', 'name')
                    ->searchable()
                    ->default(fn () => auth()->id()),
                 Tables\Filters\TernaryFilter::make('created_during_pickup')
                    ->label('Created During Pickup')
                    ->placeholder('All Checks')
                    ->trueLabel('Created During Pickup')
                    ->falseLabel('Originally Imported')
                    ->queries(
                        true: fn ($query) => $query->whereHas('product', fn ($q) => $q->where('created_during_pickup', true)),
                        false: fn ($query) => $query->where(function ($q) {
                            $q->whereDoesntHave('product')
                              ->orWhereHas('product', fn ($sq) => $sq->where('created_during_pickup', false));
                        }),
                    ),
                Tables\Filters\TernaryFilter::make('unmatched_pending_creation')
                    ->label('Unmatched (Pending Creation)')
                    ->placeholder('All Checks')
                    ->trueLabel('Pending Creation')
                    ->falseLabel('Linked / Matched')
                    ->queries(
                        true: fn ($query) => $query->where('result_status', 'UNMATCHED')->whereNull('product_id'),
                        false: fn ($query) => $query->where(function ($q) {
                            $q->where('result_status', '!=', 'UNMATCHED')
                              ->orWhereNotNull('product_id');
                        }),
                    ),
                Tables\Filters\SelectFilter::make('failure_state')
                    ->label('Review State')
                    ->options([
                        'FAILED_ONLY' => 'Failed only',
                        'REVIEW_NEEDED' => 'Failed or warning',
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): void {
                        $state = $data['value'] ?? null;

                        match ($state) {
                            'FAILED_ONLY' => $query->where('result_status', 'FAIL'),
                            'REVIEW_NEEDED' => $query->whereIn('result_status', ['FAIL', 'WARNING']),
                            default => null,
                        };
                    }),
            ])
            ->defaultSort('checked_at', 'desc')
            ->headerActions([
                Actions\Action::make('export_xlsx')
                    ->label('Export XLSX')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function ($livewire) {
                        return app(ProductCheckExportService::class)->downloadAll($livewire->getFilteredTableQuery());
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\Action::make('create_product')
                    ->label('Create Product')
                    ->icon('heroicon-o-plus-circle')
                    ->color('warning')
                    ->visible(fn (ProductCheck $record) => !$record->product_id)
                    ->form([
                        \Filament\Schemas\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('product_type_id')
                                    ->label('Product Type')
                                    ->options(\App\Models\ProductType::where('is_active', true)->pluck('name', 'id'))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, $set) => [
                                        $set('category_id', null),
                                        $set('sub_category_id', null),
                                    ]),
                                Forms\Components\Select::make('location_id')
                                    ->relationship('location', 'name')
                                    ->default(fn (ProductCheck $record) => $record->location_id)
                                    ->required(),
                                Forms\Components\Select::make('category_id')
                                    ->label('Category')
                                    ->options(function (callable $get) {
                                        $productTypeId = $get('product_type_id');
                                        if (!$productTypeId) {
                                            return [];
                                        }
                                        return \App\Models\Category::where('product_type_id', $productTypeId)->pluck('name', 'id');
                                    })
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, $set) => $set('sub_category_id', null)),
                                Forms\Components\Select::make('sub_category_id')
                                    ->label('Sub-Category')
                                    ->options(function (callable $get) {
                                        $categoryId = $get('category_id');
                                        if (!$categoryId) {
                                            return [];
                                        }
                                        return \App\Models\SubCategory::where('category_id', $categoryId)->pluck('name', 'id');
                                    })
                                    ->nullable(),
                                Forms\Components\TextInput::make('code')
                                    ->default(fn (ProductCheck $record) => $record->barcode)
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('barcode')
                                    ->default(fn (ProductCheck $record) => $record->barcode)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('qr_code')
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'ACTIVE' => 'Active',
                                        'SUSPENDED' => 'Suspended',
                                    ])
                                    ->default('ACTIVE')
                                    ->required(),
                            ]),
                        
                        \Filament\Schemas\Components\Group::make()
                            ->schema(function (callable $get) {
                                $productTypeId = $get('product_type_id');
                                if (!$productTypeId) {
                                    return [];
                                }

                                $fields = \App\Models\ProductTypeField::where('product_type_id', $productTypeId)
                                    ->where('is_active', true)
                                    ->get();

                                $schema = [];
                                foreach ($fields as $field) {
                                    $input = match ($field->field_type) {
                                        'number' => Forms\Components\TextInput::make('dynamic_attributes.' . $field->field_name)->numeric()->integer(),
                                        'decimal' => Forms\Components\TextInput::make('dynamic_attributes.' . $field->field_name)->numeric(),
                                        'date' => Forms\Components\DatePicker::make('dynamic_attributes.' . $field->field_name),
                                        'textarea' => Forms\Components\Textarea::make('dynamic_attributes.' . $field->field_name),
                                        'select' => Forms\Components\TextInput::make('dynamic_attributes.' . $field->field_name),
                                        'boolean' => Forms\Components\Select::make('dynamic_attributes.' . $field->field_name)
                                            ->options([
                                                '1' => 'Yes',
                                                '0' => 'No',
                                            ]),
                                        'branch_id' => Forms\Components\Select::make('dynamic_attributes.' . $field->field_name)
                                            ->options(\App\Models\Branch::pluck('name', 'id')->toArray()),
                                        default => Forms\Components\TextInput::make('dynamic_attributes.' . $field->field_name),
                                    };

                                    $input->label($field->field_label)
                                        ->required($field->required);

                                    $schema[] = $input;
                                }

                                return $schema;
                            })
                            ->columns(2)
                            ->key('modal_dynamic_attributes_group'),
                    ])
                    ->action(function (ProductCheck $record, array $data): void {
                        $product = \App\Models\Product::create([
                            'product_type_id' => $data['product_type_id'],
                            'location_id' => $data['location_id'],
                            'category_id' => $data['category_id'],
                            'sub_category_id' => $data['sub_category_id'],
                            'code' => $data['code'],
                            'barcode' => $data['barcode'],
                            'qr_code' => $data['qr_code'],
                            'name' => $data['name'],
                            'quantity' => $data['quantity'],
                            'status' => $data['status'],
                            'created_during_pickup' => true,
                        ]);

                        if (!empty($data['dynamic_attributes'])) {
                            foreach ($data['dynamic_attributes'] as $fieldName => $value) {
                                if ($value !== null && $value !== '') {
                                    \App\Models\ProductAttributeValue::create([
                                        'product_id' => $product->id,
                                        'field_name' => $fieldName,
                                        'value' => $value,
                                    ]);
                                }
                            }
                        }

                        $record->update([
                            'product_id' => $product->id,
                            'result_status' => 'UNMATCHED',
                        ]);

                        // Run validation to auto-complete
                        $scanConfig = $record->scanConfig;
                        if (!$scanConfig) {
                            $scanConfig = \App\Models\ScanConfig::where('product_type_id', $product->product_type_id)
                                ->where('is_active', true)
                                ->first();
                        }

                        if ($scanConfig) {
                            $actualValues = [];
                            foreach (data_get($scanConfig->config_json, 'fields', []) as $fieldConfig) {
                                $fName = $fieldConfig['field'] ?? null;
                                if (!$fName || ($fieldConfig['source'] ?? 'product') !== 'product') continue;

                                if ($fName === 'quantity') {
                                    $actualValues[$fName] = $record->quantity;
                                } else {
                                    $expectedVal = match ($fName) {
                                        'location_id', 'category_id', 'sub_category_id' => $product->{$fName},
                                        'code', 'barcode', 'qr_code', 'name', 'description', 'status' => $product->{$fName},
                                        default => $product->attributeValues->firstWhere('field_name', $fName)?->value,
                                    };
                                    $actualValues[$fName] = $expectedVal;
                                }
                            }

                            $validation = app(\App\Services\ValidationEngine::class)->validate($scanConfig, $product, $actualValues);

                            \App\Models\ProductCheckValue::where('product_check_id', $record->id)->delete();
                            foreach ($validation['values'] as $val) {
                                \App\Models\ProductCheckValue::create([
                                    'product_check_id' => $record->id,
                                    'field_name' => $val['field_name'],
                                    'expected_value' => $val['expected_value'],
                                    'actual_value' => $val['actual_value'],
                                    'difference_value' => $val['difference_value'],
                                    'status' => $val['status'],
                                ]);
                            }

                            $record->update([
                                'scan_config_id' => $scanConfig->id,
                                'remark' => !empty($validation['errors']) ? implode(' | ', $validation['errors']) : null,
                            ]);
                        }

                        event(new \App\Events\ProductChecked($record));
                    }),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ProductCheckValuesRelationManager::class,
            DecisionsRelationManager::class,
            CommentsRelationManager::class,
            AttachmentsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with('checkValues')
            ->orderByRaw("CASE result_status WHEN 'FAIL' THEN 0 WHEN 'WARNING' THEN 1 WHEN 'UNMATCHED' THEN 2 WHEN 'PASS' THEN 3 ELSE 4 END")
            ->orderByDesc('checked_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductChecks::route('/'),
            'view' => Pages\ViewProductCheck::route('/{record}'),
            'edit' => Pages\EditProductCheck::route('/{record}/edit'),
        ];
    }
}
