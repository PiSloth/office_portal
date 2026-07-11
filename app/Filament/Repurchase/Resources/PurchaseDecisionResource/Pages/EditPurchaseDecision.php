<?php

namespace App\Filament\Repurchase\Resources\PurchaseDecisionResource\Pages;

use App\Filament\Repurchase\Resources\PurchaseDecisionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseDecision extends EditRecord
{
    protected static string $resource = PurchaseDecisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;
        $uploadedFiles = $this->data['uploaded_files'] ?? [];

        // Sync attachments manually
        $currentPaths = $record->attachments->pluck('file_path')->toArray();
        $newPaths = array_diff($uploadedFiles, $currentPaths);
        $deletePaths = array_diff($currentPaths, $uploadedFiles);

        foreach ($newPaths as $path) {
            if ($path) {
                \App\Models\Attachment::create([
                    'attachable_type' => get_class($record),
                    'attachable_id' => $record->id,
                    'file_path' => $path,
                ]);
            }
        }

        if (!empty($deletePaths)) {
            $record->attachments()->whereIn('file_path', $deletePaths)->delete();
        }
    }
}
