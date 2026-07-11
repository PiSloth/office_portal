<?php

namespace App\Filament\Repurchase\Resources;

use App\Filament\Repurchase\Resources\PurchaseDecisionResource\Pages;
use App\Modules\Purchase\Models\PurchaseDecision;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchaseRequest.branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->sortable(),
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
