<?php

namespace App\Filament\Repurchase\Resources\PurchaseDecisionResource\Pages;

use App\Filament\Repurchase\Resources\PurchaseDecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseDecision extends ViewRecord
{
    protected static string $resource = PurchaseDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('quick_close')
                ->label('Quick Close')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'open')
                ->modalHeading(fn (): string => "Quick Close Decision #{$this->record->id}")
                ->modalDescription('Enter resolution remarks and optional proof images to close this decision.')
                ->modalWidth('lg')
                ->modalSubmitActionLabel('Confirm & Close')
                ->form([
                    \Filament\Forms\Components\Textarea::make('remark')
                        ->label('Remarks / Action Taken')
                        ->placeholder('Enter resolution details...')
                        ->required()
                        ->rows(3),
                    \Filament\Forms\Components\FileUpload::make('uploaded_files')
                        ->label('Proof of Resolution (Optional Images)')
                        ->multiple()
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->directory('attachments/purchase_decisions'),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => 'closed',
                        'remark' => $data['remark'],
                    ]);

                    $uploadedFiles = $data['uploaded_files'] ?? [];
                    foreach ($uploadedFiles as $path) {
                        if ($path) {
                            \App\Models\Attachment::create([
                                'attachable_type' => get_class($this->record),
                                'attachable_id' => $this->record->id,
                                'file_path' => $path,
                            ]);
                        }
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Decision Closed')
                        ->body("Decision #{$this->record->id} has been marked as closed.")
                        ->success()
                        ->send();

                    $this->redirect(PurchaseDecisionResource::getUrl('index'));
                }),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
