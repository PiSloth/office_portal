<?php

namespace App\Filament\Resources\ValidationRuleSetResource\Pages;

use App\Filament\Resources\ValidationRuleSetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditValidationRuleSet extends EditRecord
{
    protected static string $resource = ValidationRuleSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
