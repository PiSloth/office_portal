<?php

namespace App\Filament\Stock\Resources\DecisionTypeResource\Pages;

use App\Filament\Stock\Resources\DecisionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDecisionType extends EditRecord
{
    protected static string $resource = DecisionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
