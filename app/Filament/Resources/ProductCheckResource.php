<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\HasPermissionGates;
use App\Filament\Resources\ProductCheckResource\Pages;
use App\Filament\Resources\ProductCheckResource\RelationManagers\AttachmentsRelationManager;
use App\Filament\Resources\ProductCheckResource\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\ProductCheckResource\RelationManagers\DecisionsRelationManager;
use App\Filament\Resources\ProductCheckResource\RelationManagers\ProductCheckValuesRelationManager;
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
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('product.code')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product Name')
                    ->searchable()
                    ->limit(32),
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
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PASS' => 'success',
                        'FAIL' => 'danger',
                        'WARNING' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('checked_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('remark')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('check_session_id')
                    ->label('Session')
                    ->relationship('checkSession', 'name'),
                Tables\Filters\SelectFilter::make('scan_config_id')
                    ->label('Scan Config')
                    ->relationship('scanConfig', 'name'),
                Tables\Filters\SelectFilter::make('result_status')
                    ->options([
                        'PASS' => 'Pass',
                        'FAIL' => 'Fail',
                        'WARNING' => 'Warning',
                    ]),
                Tables\Filters\SelectFilter::make('checked_by')
                    ->label('Checked By')
                    ->relationship('checkedBy', 'name'),
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
                    ->action(function () {
                        return app(ProductCheckExportService::class)->downloadAll();
                    }),
            ])
            ->actions([
                Actions\ViewAction::make(),
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
            ->orderByRaw("CASE result_status WHEN 'FAIL' THEN 0 WHEN 'WARNING' THEN 1 WHEN 'PASS' THEN 2 ELSE 3 END")
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
