<?php

namespace App\Filament\Repurchase\Resources;

use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Filament\Repurchase\Resources\PurchaseDecisionResource\Pages;
use App\Modules\Purchase\Models\PurchaseDecision;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseDecisionResource extends Resource
{
    use HasPermissionGates;

    protected static string $permissionPrefix = 'decisions';
    protected static ?string $model = PurchaseDecision::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static \UnitEnum|string|null $navigationGroup = 'Purchase';
    protected static ?string $navigationLabel = 'Purchase Decisions';
    protected static ?string $modelLabel = 'Purchase Decision';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Schemas\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\Placeholder::make('product_info_view')
                            ->hiddenLabel()
                            ->content(fn (?PurchaseDecision $record) => view('filament.repurchase.decision-product-info', ['record' => $record])),
                    ])
                    ->visible(fn (?PurchaseDecision $record) => $record !== null && $record->purchaseRequest !== null)
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Original Validation Failure Reference')
                    ->schema([
                        Forms\Components\Placeholder::make('failed_fields')
                            ->label('Failed Fields')
                            ->content(fn (?PurchaseDecision $record): string => $record?->purchaseRequest?->failChecks?->pluck('field_name')->unique()->join(', ') ?? '-'),
                        Forms\Components\Placeholder::make('expected_values')
                            ->label('Expected Values')
                            ->content(fn (?PurchaseDecision $record): string => $record?->purchaseRequest?->failChecks?->map(fn($fc) => "{$fc->field_name}: {$fc->expected_value}")->join(' | ') ?? '-'),
                        Forms\Components\Placeholder::make('actual_values')
                            ->label('Actual Values')
                            ->content(fn (?PurchaseDecision $record): string => $record?->purchaseRequest?->failChecks?->map(fn($fc) => "{$fc->field_name}: {$fc->actual_value}")->join(' | ') ?? '-'),
                        Forms\Components\Placeholder::make('checked_by')
                            ->label('Who Checked / Checked By')
                            ->content(fn (?PurchaseDecision $record): string => $record?->purchaseRequest?->failChecks?->map(fn($fc) => $fc->whoChecked?->name)->filter()->unique()->join(', ') ?? '-'),
                        Forms\Components\Placeholder::make('check_remarks')
                            ->label('Check Remarks')
                            ->content(fn (?PurchaseDecision $record): string => $record?->purchaseRequest?->failChecks?->map(fn($fc) => "{$fc->field_name}: {$fc->remark}")->join(' | ') ?? '-')
                            ->columnSpan(2),
                    ])
                    ->columns(3)
                    ->visible(fn (?PurchaseDecision $record) => $record !== null)
                    ->columnSpanFull(),

                \Filament\Schemas\Components\Section::make('Check History Summary on Failed Fields')
                    ->schema([
                        Forms\Components\Placeholder::make('failed_fields_histories')
                            ->hiddenLabel()
                            ->content(fn (?PurchaseDecision $record) => view('filament.repurchase.decision-histories-table', ['record' => $record])),
                    ])
                    ->visible(fn (?PurchaseDecision $record) => $record !== null && $record->purchaseRequest?->failChecks?->isNotEmpty())
                    ->columnSpanFull(),

                Forms\Components\Select::make('purchase_request_id')
                    ->relationship('purchaseRequest', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->purchase_number)
                    ->disabled()
                    ->required()
                    ->label('Purchase Request No'),

                Forms\Components\Select::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed (Resolved)',
                    ])
                    ->required()
                    ->live()
                    ->label('Decision Status'),

                Forms\Components\Textarea::make('remark')
                    ->columnSpanFull()
                    ->label('Remarks / Action Taken'),

                Forms\Components\FileUpload::make('uploaded_files')
                    ->multiple()
                    ->image()
                    ->disk('public')
                    ->visibility('public')
                    ->directory('attachments/purchase_decisions')
                    ->label('Proof of Resolution (Images)')
                    ->formatStateUsing(fn ($record) => $record?->attachments->pluck('file_path')->toArray() ?? [])
                    ->dehydrated(false)
                    ->required(fn (callable $get) => $get('status') === 'closed')
                    ->validationMessages([
                        'required' => 'At least one image attachment is required when closing/resolving the decision.',
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('purchaseRequest.purchase_number')
                    ->label('Purchase Request No')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        $search = trim($search);
                        
                        // 1. Full pattern: PR-BRANCHCODE/YYMMDDNNN
                        if (preg_match('/^PR-([A-Z0-9]+)\/(\d{6})(\d{3})$/i', $search, $matches)) {
                            $branchCode = $matches[1];
                            $dateStr = $matches[2];
                            $seq = intval($matches[3]);
                            
                            try {
                                $date = \Carbon\Carbon::createFromFormat('ymd', $dateStr)->toDateString();
                                
                                $id = \App\Modules\Purchase\Models\PurchaseRequest::whereDate('created_at', $date)
                                    ->whereHas('branch', fn($q) => $q->where('code', $branchCode))
                                    ->orderBy('id', 'asc')
                                    ->skip($seq - 1)
                                    ->take(1)
                                    ->value('id');
                                
                                if ($id) {
                                    return $query->where('purchase_request_id', $id);
                                }
                            } catch (\Exception $e) {
                            }
                        }
                        
                        // 2. Date only: YYMMDD
                        if (preg_match('/^\d{6}$/', $search)) {
                            try {
                                $date = \Carbon\Carbon::createFromFormat('ymd', $search)->toDateString();
                                return $query->whereHas('purchaseRequest', fn($q) => $q->whereDate('created_at', $date));
                            } catch (\Exception $e) {
                            }
                        }

                        // 3. Branch only starting with PR-: PR-BRANCHCODE
                        if (preg_match('/^PR-([A-Z0-9]+)$/i', $search, $matches)) {
                            $branchCode = $matches[1];
                            return $query->whereHas('purchaseRequest.branch', fn($q) => $q->where('code', 'like', "%{$branchCode}%"));
                        }

                        // 4. Default fallback: search branch code or purchase request ID
                        return $query->where(function (Builder $q) use ($search) {
                            $q->whereHas('purchaseRequest.branch', fn($sub) => $sub->where('code', 'like', "%{$search}%"))
                              ->orWhere('purchase_request_id', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->join('purchase_requests', 'purchase_decisions.purchase_request_id', '=', 'purchase_requests.id')
                                     ->orderBy('purchase_requests.created_at', $direction)
                                     ->orderBy('purchase_requests.id', $direction)
                                     ->select('purchase_decisions.*');
                    }),
                Tables\Columns\TextColumn::make('purchaseRequest.branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseRequest.customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('failed_fields')
                    ->label('Failed Fields')
                    ->state(fn (PurchaseDecision $record): string => $record->purchaseRequest?->failChecks?->pluck('field_name')->unique()->join(', ') ?? '-'),
                Tables\Columns\TextColumn::make('expected_values')
                    ->label('Expected Values')
                    ->state(fn (PurchaseDecision $record): string => $record->purchaseRequest?->failChecks?->map(fn($fc) => "{$fc->field_name}: {$fc->expected_value}")->join(' | ') ?? '-'),
                Tables\Columns\TextColumn::make('actual_values')
                    ->label('Actual Values')
                    ->state(fn (PurchaseDecision $record): string => $record->purchaseRequest?->failChecks?->map(fn($fc) => "{$fc->field_name}: {$fc->actual_value}")->join(' | ') ?? '-'),
                Tables\Columns\TextColumn::make('checked_by')
                    ->label('Checked By')
                    ->state(fn (PurchaseDecision $record): string => $record->purchaseRequest?->failChecks?->map(fn($fc) => $fc->whoChecked?->name)->filter()->unique()->join(', ') ?? '-'),
                Tables\Columns\TextColumn::make('check_remarks')
                    ->label('Check Remarks')
                    ->state(fn (PurchaseDecision $record): string => $record->purchaseRequest?->failChecks?->map(fn($fc) => "{$fc->field_name}: {$fc->remark}")->join(' | ') ?? '-')
                    ->limit(50),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning',
                        'closed' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('remark')
                    ->limit(50),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Open',
                        'closed' => 'Closed',
                    ])
                    ->default('open'),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Branch')
                    ->options(fn () => \App\Models\Branch::pluck('name', 'id')->all())
                    ->searchable()
                    ->default(fn () => auth()->user()?->branch_id)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['value']),
                            fn (Builder $q) => $q->whereHas('purchaseRequest', fn ($sq) => $sq->where('branch_id', $data['value']))
                        );
                    }),
                Tables\Filters\Filter::make('request_number')
                    ->label('Request Number')
                    ->form([
                        Forms\Components\TextInput::make('request_number')
                            ->label('Request Number')
                            ->placeholder('e.g. PR-MAIN/260711001 or ID'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['request_number'])) {
                            $search = trim($data['request_number']);
                            return $query->whereHas('purchaseRequest', function ($q) use ($search) {
                                $q->where(function ($sub) use ($search) {
                                    $sub->where('purchase_number', 'like', "%{$search}%")
                                        ->orWhere('id', 'like', "%{$search}%");
                                });
                            });
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!empty($data['request_number'])) {
                            return 'Request No: ' . $data['request_number'];
                        }
                        return null;
                    }),
                Tables\Filters\Filter::make('customer')
                    ->label('Customer')
                    ->form([
                        Forms\Components\TextInput::make('customer_search')
                            ->label('Customer')
                            ->placeholder('Name, Phone, or NRC'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['customer_search'])) {
                            $search = trim($data['customer_search']);
                            return $query->whereHas('purchaseRequest', function ($q) use ($search) {
                                $q->where(function ($sub) use ($search) {
                                    $sub->where('customer_name', 'like', "%{$search}%")
                                        ->orWhere('customer_phone', 'like', "%{$search}%")
                                        ->orWhere('customer_nrc', 'like', "%{$search}%");
                                });
                            });
                        }
                        return $query;
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!empty($data['customer_search'])) {
                            return 'Customer: ' . $data['customer_search'];
                        }
                        return null;
                    }),
                Tables\Filters\SelectFilter::make('failed_field_type')
                    ->label('Failed Field Type')
                    ->options(function () {
                        $fromFails = \App\Modules\Purchase\Models\FailCheck::distinct()->pluck('field_name', 'field_name');
                        $fromRules = \App\Modules\Core\Validation\Models\ValidationRule::distinct()->pluck('field_name', 'field_name');
                        return $fromFails->merge($fromRules)
                            ->filter()
                            ->mapWithKeys(fn ($item) => [$item => ucwords(str_replace('_', ' ', $item))])
                            ->all();
                    })
                    ->searchable()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            !empty($data['value']),
                            fn (Builder $q) => $q->whereHas('purchaseRequest.failChecks', fn ($sq) => $sq->where('field_name', $data['value']))
                        );
                    }),
                Tables\Filters\Filter::make('created_at')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $q, $date): Builder => $q->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['created_from'])) {
                            $indicators['created_from'] = 'From: ' . \Carbon\Carbon::parse($data['created_from'])->toFormattedDateString();
                        }
                        if (!empty($data['created_until'])) {
                            $indicators['created_until'] = 'Until: ' . \Carbon\Carbon::parse($data['created_until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
            ])
            ->filtersFormColumns(2)
            ->groups([
                Tables\Grouping\Group::make('purchase_request_id')
                    ->label('Purchase Request')
                    ->getTitleFromRecordUsing(function ($record) {
                        $pr = $record->purchaseRequest;
                        if (!$pr) {
                            return "Request #{$record->purchase_request_id}";
                        }
                        $customer = $pr->customer_name ? " - {$pr->customer_name}" : '';
                        $branch = $pr->branch?->name ? " ({$pr->branch->name})" : '';

                        return "{$pr->purchase_number}{$customer}{$branch}";
                    })
                    ->titlePrefixedWithLabel(false)
                    ->collapsible(),
            ])
            ->defaultGroup('purchase_request_id')
            ->collapsedGroupsByDefault()
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Section::make('Product Information')
                ->schema([
                    Forms\Components\Placeholder::make('product_info_view')
                        ->hiddenLabel()
                        ->content(fn (?PurchaseDecision $record) => view('filament.repurchase.decision-product-info', ['record' => $record])),
                ])
                ->visible(fn (?PurchaseDecision $record) => $record !== null && $record->purchaseRequest !== null)
                ->columnSpanFull(),

            \Filament\Schemas\Components\Section::make('Original Validation Failure Reference')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('failed_fields')
                        ->label('Failed Fields')
                        ->state(fn (?PurchaseDecision $record): string => $record?->purchaseRequest?->failChecks?->pluck('field_name')->unique()->join(', ') ?? '-'),
                    \Filament\Infolists\Components\TextEntry::make('expected_values')
                        ->label('Expected Values')
                        ->state(fn (?PurchaseDecision $record): string => $record?->purchaseRequest?->failChecks?->map(fn($fc) => "{$fc->field_name}: {$fc->expected_value}")->join(' | ') ?? '-'),
                    \Filament\Infolists\Components\TextEntry::make('actual_values')
                        ->label('Actual Values')
                        ->state(fn (?PurchaseDecision $record): string => $record?->purchaseRequest?->failChecks?->map(fn($fc) => "{$fc->field_name}: {$fc->actual_value}")->join(' | ') ?? '-'),
                    \Filament\Infolists\Components\TextEntry::make('checked_by')
                        ->label('Checked By')
                        ->state(fn (?PurchaseDecision $record): string => $record?->purchaseRequest?->failChecks?->map(fn($fc) => $fc->whoChecked?->name)->filter()->unique()->join(', ') ?? '-'),
                    \Filament\Infolists\Components\TextEntry::make('check_remarks')
                        ->label('Check Remarks')
                        ->state(fn (?PurchaseDecision $record): string => $record?->purchaseRequest?->failChecks?->map(fn($fc) => "{$fc->field_name}: {$fc->remark}")->join(' | ') ?? '-')
                        ->columnSpan(2),
                ])
                ->columns(3)
                ->visible(fn (?PurchaseDecision $record) => $record !== null)
                ->columnSpanFull(),

            \Filament\Schemas\Components\Section::make('Check History Summary on Failed Fields')
                ->schema([
                    Forms\Components\Placeholder::make('failed_fields_histories')
                        ->hiddenLabel()
                        ->content(fn (?PurchaseDecision $record) => view('filament.repurchase.decision-histories-table', ['record' => $record])),
                ])
                ->visible(fn (?PurchaseDecision $record) => $record !== null && $record->purchaseRequest?->failChecks?->isNotEmpty())
                ->columnSpanFull(),

            \Filament\Schemas\Components\Section::make('Decision Details')
                ->schema([
                    \Filament\Infolists\Components\TextEntry::make('purchaseRequest.purchase_number')
                        ->label('Purchase Request No'),
                    \Filament\Infolists\Components\TextEntry::make('status')
                        ->label('Decision Status')
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                            'open' => 'warning',
                            'closed' => 'success',
                            default => 'gray',
                        }),
                    \Filament\Infolists\Components\TextEntry::make('remark')
                        ->label('Remarks / Action Taken')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseDecisions::route('/'),
            'view' => Pages\ViewPurchaseDecision::route('/{record}'),
            'edit' => Pages\EditPurchaseDecision::route('/{record}/edit'),
        ];
    }
}
