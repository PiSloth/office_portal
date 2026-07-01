<?php

namespace App\Filament\Stock\Resources\DecisionRuleResource\Pages;

use App\Filament\Stock\Resources\DecisionRuleResource;
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
