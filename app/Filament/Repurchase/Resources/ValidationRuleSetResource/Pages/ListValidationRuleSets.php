<?php

namespace App\Filament\Repurchase\Resources\ValidationRuleSetResource\Pages;

use App\Filament\Repurchase\Resources\ValidationRuleSetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListValidationRuleSets extends ListRecords
{
    protected static string $resource = ValidationRuleSetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
