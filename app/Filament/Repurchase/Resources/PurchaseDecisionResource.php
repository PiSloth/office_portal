<?php

namespace App\Filament\Repurchase\Resources;

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
    protected static ?string $model = PurchaseDecision::class;
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static \UnitEnum|string|null $navigationGroup = 'Purchase';
    protected static ?string $navigationLabel = 'Purchase Decisions';
    protected static ?string $modelLabel = 'Purchase Decision';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
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
                    ]),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
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
            'edit' => Pages\EditPurchaseDecision::route('/{record}/edit'),
        ];
    }
}
