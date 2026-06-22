<?php

namespace App\Filament\Resources\ProductCheckResource\Pages;

use App\Filament\Resources\DecisionResource;
use App\Filament\Resources\ProductCheckResource;
use App\Models\Comment;
use App\Models\Decision;
use App\Models\DecisionType;
use Filament\Forms;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProductCheck extends ViewRecord
{
    protected static string $resource = ProductCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('add_decision')
                ->label('Add Decision')
                ->icon('heroicon-o-flag')
                ->visible(fn () => $this->record->result_status !== 'PASS')
                ->form([
                    Forms\Components\Select::make('decision_type_id')
                        ->label('Decision Type')
                        ->options(DecisionType::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->required(),
                    Forms\Components\Select::make('action_status')
                        ->options([
                            'OPEN' => 'Open',
                            'IN_PROGRESS' => 'In Progress',
                            'DONE' => 'Done',
                            'REJECTED' => 'Rejected',
                        ])
                        ->default('OPEN')
                        ->required(),
                    Forms\Components\Select::make('assigned_to')
                        ->options(\App\Models\User::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->nullable(),
                    Forms\Components\Textarea::make('remark')
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    $decision = Decision::create([
                        'product_check_id' => $this->record->id,
                        'decision_type_id' => $data['decision_type_id'],
                        'action_status' => $data['action_status'],
                        'assigned_to' => $data['assigned_to'] ?? null,
                        'decision_by' => auth()->id(),
                        'remark' => $data['remark'] ?? null,
                    ]);

                    $this->record->refresh();

                    Notification::make()
                        ->title('Decision created')
                        ->body("Decision #{$decision->id} has been added to this check.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('add_comment')
                ->label('Add Comment')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->visible(fn () => $this->record->decisions()->exists())
                ->form([
                    Forms\Components\Select::make('decision_id')
                        ->label('Decision')
                        ->options(fn () => $this->record->decisions()
                            ->with('decisionType')
                            ->get()
                            ->mapWithKeys(fn ($decision) => [
                                $decision->id => sprintf(
                                    '#%s - %s (%s)',
                                    $decision->id,
                                    $decision->decisionType?->name ?? 'Decision',
                                    $decision->action_status,
                                ),
                            ])
                            ->all())
                        ->required(),
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
                ->action(function (array $data): void {
                    Comment::create([
                        'decision_id' => $data['decision_id'],
                        'user_id' => auth()->id(),
                        'comment_type' => $data['comment_type'],
                        'comment' => $data['comment'],
                    ]);

                    Notification::make()
                        ->title('Comment added')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('open_decisions')
                ->label('Open Decisions')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => DecisionResource::getUrl('index')),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
