<?php

namespace App\Filament\Stock\Resources\ProductCheckResource\RelationManagers;

use App\Models\Decision;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Tables;
use Filament\Tables\Table;

class DecisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'decisions';

    protected static ?string $title = 'Decisions';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('decisionType.name')->label('Type')->sortable(),
                Tables\Columns\TextColumn::make('action_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'OPEN' => 'warning',
                        'IN_PROGRESS' => 'info',
                        'DONE' => 'success',
                        'REJECTED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('assignedTo.name')->label('Assigned To')->sortable(),
                Tables\Columns\TextColumn::make('decisionBy.name')->label('Decision By')->sortable(),
                Tables\Columns\TextColumn::make('remark')->limit(50),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->schema([
                        Section::make('Decision Review')
                            ->schema([
                                TextEntry::make('decisionType.name')->label('Type')->badge(),
                                TextEntry::make('action_status')
                                    ->label('Action Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'OPEN' => 'warning',
                                        'IN_PROGRESS' => 'info',
                                        'DONE' => 'success',
                                        'REJECTED' => 'danger',
                                        default => 'gray',
                                    }),
                                TextEntry::make('assignedTo.name')->label('Assigned To'),
                                TextEntry::make('decisionBy.name')->label('Decision By'),
                                TextEntry::make('remark')->label('Remark')->columnSpanFull(),
                            ])
                            ->columns(2),

                        Section::make('Comments')
                            ->schema([
                                RepeatableEntry::make('comments')
                                    ->state(fn (Decision $record) => $record->comments()
                                        ->with('user')
                                        ->latest()
                                        ->get()
                                        ->map(fn ($comment) => [
                                            'user_name' => $comment->user?->name ?? 'User',
                                            'comment_type' => $comment->comment_type,
                                            'comment' => $comment->comment,
                                            'created_at' => optional($comment->created_at)->toDateTimeString(),
                                        ])
                                        ->all())
                                    ->schema([
                                        TextEntry::make('user_name')->label('User'),
                                        TextEntry::make('comment_type')->label('Type')->badge(),
                                        TextEntry::make('comment')->label('Comment')->columnSpanFull(),
                                        TextEntry::make('created_at')->label('Created At'),
                                    ])
                                    ->columns(4),
                            ]),
                    ])
                    ->extraModalFooterActions([
                        Actions\Action::make('mark_done')
                            ->label('Mark Done')
                            ->icon('heroicon-o-check-circle')
                            ->color('success')
                            ->action(function (Decision $record): void {
                                $record->update(['action_status' => 'DONE']);
                                $this->resetTable();
                            }),
                        Actions\Action::make('change_status')
                            ->label('Change Status')
                            ->icon('heroicon-o-arrow-path')
                            ->color('info')
                            ->form([
                                Forms\Components\Select::make('action_status')
                                    ->options([
                                        'OPEN' => 'Open',
                                        'IN_PROGRESS' => 'In Progress',
                                        'DONE' => 'Done',
                                        'REJECTED' => 'Rejected',
                                    ])
                                    ->required(),
                                Forms\Components\Textarea::make('remark')
                                    ->label('Status Remark')
                                    ->helperText('Optional note for the status change.')
                                    ->columnSpanFull(),
                            ])
                            ->action(function (Decision $record, array $data): void {
                                $record->update([
                                    'action_status' => $data['action_status'],
                                    'remark' => filled($data['remark'] ?? null)
                                        ? $data['remark']
                                        : $record->remark,
                                ]);
                                $this->resetTable();
                            }),
                        Actions\Action::make('add_comment')
                            ->label('Add Comment')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->color('gray')
                            ->form([
                                Forms\Components\Select::make('comment_type')
                                    ->options([
                                        'DECISION' => 'Decision',
                                        'LOG' => 'Log',
                                    ])
                                    ->default('DECISION')
                                    ->required(),
                                Forms\Components\Textarea::make('comment')
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->action(function (Decision $record, array $data): void {
                                $record->comments()->create([
                                    'user_id' => auth()->id(),
                                    'comment_type' => $data['comment_type'],
                                    'comment' => $data['comment'],
                                ]);
                                $this->resetTable();
                            }),
                    ]),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
