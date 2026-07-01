<?php

namespace App\Filament\Stock\Resources\DecisionRuleResource\Pages;

use App\Filament\Stock\Resources\DecisionRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDecisionRule extends EditRecord
{
    protected static string $resource = DecisionRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
