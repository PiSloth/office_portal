<?php

namespace App\Filament\Stock\Resources\DecisionTypeResource\Pages;

use App\Filament\Stock\Resources\DecisionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDecisionTypes extends ListRecords
{
    protected static string $resource = DecisionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
