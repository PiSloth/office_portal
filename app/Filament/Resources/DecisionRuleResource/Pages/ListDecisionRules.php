<?php

namespace App\Filament\Resources\DecisionRuleResource\Pages;

use App\Filament\Resources\DecisionRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDecisionRules extends ListRecords
{
    protected static string $resource = DecisionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
